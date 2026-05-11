<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\SmsCampaign;
use App\Services\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 7200; // 2 hours

    public function __construct(
        public SmsCampaign $campaign,
    ) {}

    public function handle(ActivityLogger $activityLogger): void
    {
        // Refresh campaign status
        $this->campaign->refresh();

        if ($this->campaign->isCancelled()) {
            Log::info('Campaign cancelled, stopping send', ['campaign_id' => $this->campaign->id]);

            return;
        }

        // Mark as sending
        $this->campaign->markSending();
        $activityLogger->logCampaignSending($this->campaign->fresh());

        $rateLimit = config('sms.rate_limit_per_minute', 300);
        $batchSize = min(50, $rateLimit);
        $delayBetweenBatches = max(1, (60 / ($rateLimit / $batchSize)));

        $totalProcessed = 0;

        CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->chunkById($batchSize, function ($recipients) use ($delayBetweenBatches, &$totalProcessed) {
                // Check if campaign is still active before each batch
                $this->campaign->refresh();

                if ($this->campaign->isPaused()) {
                    Log::info('Campaign paused, stopping send', ['campaign_id' => $this->campaign->id]);

                    return false; // Stop chunking
                }

                if ($this->campaign->isCancelled()) {
                    Log::info('Campaign cancelled, stopping send', ['campaign_id' => $this->campaign->id]);

                    return false; // Stop chunking
                }

                foreach ($recipients as $recipient) {
                    $recipient->update([
                        'status' => 'queued',
                        'queued_at' => now(),
                    ]);

                    SendSingleSms::dispatch($this->campaign, $recipient->fresh());
                    $totalProcessed++;
                }

                $this->refreshCampaignCounts();

                // Rate limit delay
                if ($delayBetweenBatches > 0 && ! app()->runningUnitTests()) {
                    sleep((int) $delayBetweenBatches);
                }
            });

        $this->refreshCampaignCounts();
        Log::info('Campaign send dispatch completed', [
            'campaign_id' => $this->campaign->id,
            'queued_recipients' => $totalProcessed,
        ]);
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
