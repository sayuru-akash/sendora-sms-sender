<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Services\ActivityLogger;
use App\Services\Sms\MessagePersonalizer;
use App\Services\Sms\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendSingleSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120; // 2 minutes

    public function __construct(
        public SmsCampaign $campaign,
        public CampaignRecipient $recipient,
    ) {}

    public function handle(SmsService $smsService, MessagePersonalizer $messagePersonalizer, ActivityLogger $activityLogger): void
    {
        // Refresh campaign status
        $this->campaign->refresh();

        if (! $this->campaign->isSending() && ! $this->campaign->isQueued()) {
            Log::info('Campaign no longer active, skipping SMS', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'status' => $this->campaign->status,
            ]);

            return;
        }

        $recipient = CampaignRecipient::with('contact')->find($this->recipient->id);

        if (! $recipient || $recipient->isSent()) {
            return;
        }

        $this->recipient = $recipient;

        $sentMessage = SmsMessage::where('campaign_recipient_id', $this->recipient->id)
            ->where('status', 'sent')
            ->latest()
            ->first();

        if ($sentMessage) {
            $this->recipient->update([
                'status' => 'sent',
                'sent_at' => $sentMessage->sent_at ?? now(),
                'provider_message_id' => $sentMessage->provider_message_id,
                'provider_response' => $sentMessage->provider_response,
            ]);
            $this->refreshCampaignCounts();

            return;
        }

        if (! $this->recipient->isQueued()) {
            Log::info('Campaign recipient is no longer queued, skipping SMS', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'status' => $this->recipient->status,
            ]);

            return;
        }

        $claimed = CampaignRecipient::whereKey($this->recipient->id)
            ->where('status', 'queued')
            ->where('attempt_count', $this->recipient->attempt_count)
            ->update([
                'attempt_count' => DB::raw('attempt_count + 1'),
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            Log::info('Campaign recipient was claimed by another send job', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
            ]);

            return;
        }

        $this->recipient->refresh();
        $this->recipient->loadMissing('contact');

        $messageBody = $this->recipient->personalised_message;
        if (! $messageBody && $this->recipient->contact) {
            $messageBody = $messagePersonalizer->render($this->campaign->message_body, $this->recipient->contact);
        }
        $messageBody ??= $this->campaign->message_body;
        $senderId = $this->campaign->sender_id ?? config('sms.source');

        // Create SMS message log
        $smsMessage = SmsMessage::create([
            'campaign_id' => $this->campaign->id,
            'campaign_recipient_id' => $this->recipient->id,
            'contact_id' => $this->recipient->contact_id,
            'phone_normalised' => $this->recipient->phone_normalised,
            'message_body' => $messageBody,
            'provider' => $smsService->getProvider()->getName(),
            'status' => 'pending',
        ]);

        $unresolvedPlaceholders = $messagePersonalizer->unresolvedPlaceholders($messageBody);
        if (! empty($unresolvedPlaceholders)) {
            $errorMessage = 'Message contains unresolved placeholders: '.implode(', ', $unresolvedPlaceholders);

            $this->recipient->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $errorMessage,
            ]);

            $smsMessage->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'failed_at' => now(),
            ]);
            $smsMessage->refresh();

            $this->refreshCampaignCounts();
            $activityLogger->logCampaignRecipientFailed($this->campaign, $this->recipient, $smsMessage, $errorMessage);

            Log::warning('SMS send blocked due to unresolved placeholders', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'placeholders' => $unresolvedPlaceholders,
            ]);

            return;
        }

        // Send SMS
        $result = $smsService->send($this->recipient->phone_normalised, $messageBody, $senderId);

        if ($result->success) {
            // Update recipient
            $this->recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
                'provider_message_id' => $result->providerMessageId,
                'provider_response' => $result->rawResponse,
            ]);

            // Update SMS message
            $smsMessage->update([
                'status' => 'sent',
                'provider_message_id' => $result->providerMessageId,
                'provider_response' => $result->rawResponse,
                'sent_at' => now(),
            ]);
            $smsMessage->refresh();

            // Update campaign counts
            $this->refreshCampaignCounts();
            $activityLogger->logCampaignRecipientSent($this->campaign, $this->recipient, $smsMessage);
        } else {
            // Update recipient
            $this->recipient->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $result->errorMessage,
                'provider_response' => $result->rawResponse,
            ]);

            // Update SMS message
            $smsMessage->update([
                'status' => 'failed',
                'error_message' => $result->errorMessage,
                'provider_response' => $result->rawResponse,
                'failed_at' => now(),
            ]);
            $smsMessage->refresh();

            // Update campaign counts
            $this->refreshCampaignCounts();
            $activityLogger->logCampaignRecipientFailed(
                $this->campaign,
                $this->recipient,
                $smsMessage,
                $result->errorMessage ?? 'SMS provider rejected the message.'
            );
        }
    }

    private function refreshCampaignCounts(): void
    {
        $counts = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $this->campaign->forceFill([
            'pending_count' => (int) ($counts['pending'] ?? 0) + (int) ($counts['queued'] ?? 0),
            'queued_count' => (int) ($counts['queued'] ?? 0),
            'sent_count' => (int) ($counts['sent'] ?? 0),
            'failed_count' => (int) ($counts['failed'] ?? 0),
            'skipped_count' => (int) ($counts['skipped'] ?? 0),
        ])->save();
    }
}
