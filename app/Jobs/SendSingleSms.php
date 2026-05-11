<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Services\Sms\MessagePersonalizer;
use App\Services\Sms\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    public function handle(SmsService $smsService, MessagePersonalizer $messagePersonalizer): void
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

        // Update attempt count
        $this->recipient->increment('attempt_count');

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

            $this->campaign->increment('failed_count');
            $this->campaign->decrement('pending_count');

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

            // Update campaign counts
            $this->campaign->increment('sent_count');
            $this->campaign->decrement('pending_count');
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

            // Update campaign counts
            $this->campaign->increment('failed_count');
            $this->campaign->decrement('pending_count');
        }
    }
}
