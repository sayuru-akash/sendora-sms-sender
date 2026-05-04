<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\SmsCampaign;
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

    public function handle(): void
    {
        // Refresh campaign status
        $this->campaign->refresh();

        if ($this->campaign->isCancelled()) {
            Log::info('Campaign cancelled, stopping send', ['campaign_id' => $this->campaign->id]);
            return;
        }

        // Mark as sending
        $this->campaign->markSending();

        $rateLimit = config('sms.rate_limit_per_minute', 300);
        $batchSize = min(50, $rateLimit);
        $delayBetweenBatches = max(1, (60 / ($rateLimit / $batchSize)));

        $pendingRecipients = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->where('status', 'pending')
            ->orderBy('id');

        $totalProcessed = 0;

        $pendingRecipients->chunk($batchSize, function ($recipients) use ($delayBetweenBatches, &$totalProcessed) {
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
                SendSingleSms::dispatch($this->campaign, $recipient);
                $totalProcessed++;

                // Update queued count
                $recipient->update([
                    'status' => 'queued',
                    'queued_at' => now(),
                ]);
            }

            // Update campaign queued count
            $this->campaign->update(['queued_count' => $totalProcessed]);

            // Rate limit delay
            if ($delayBetweenBatches > 0) {
                sleep((int) $delayBetweenBatches);
            }
        });
    }
}
