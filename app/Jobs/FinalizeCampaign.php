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

class FinalizeCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 300; // 5 minutes

    public function __construct(
        public SmsCampaign $campaign,
    ) {}

    public function handle(): void
    {
        $this->campaign->refresh();

        // If campaign is cancelled, mark as cancelled
        if ($this->campaign->isCancelled()) {
            Log::info('Campaign was cancelled, finalizing as cancelled', [
                'campaign_id' => $this->campaign->id,
            ]);
            return;
        }

        // Count remaining pending/queued recipients
        $pendingCount = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->whereIn('status', ['pending', 'queued'])
            ->count();

        if ($pendingCount > 0) {
            // There are still pending recipients - wait and retry
            // Re-dispatch with delay
            Log::info('Campaign still has pending recipients, re-dispatching finalize', [
                'campaign_id' => $this->campaign->id,
                'pending_count' => $pendingCount,
            ]);

            self::dispatch($this->campaign)->delay(now()->addMinutes(1));
            return;
        }

        // Update final counts from database
        $counts = CampaignRecipient::where('campaign_id', $this->campaign->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $this->campaign->update([
            'sent_count' => $counts->get('sent', 0),
            'failed_count' => $counts->get('failed', 0),
            'skipped_count' => $counts->get('skipped', 0),
            'pending_count' => 0,
            'queued_count' => 0,
        ]);

        // Mark as completed or failed
        $sentCount = $this->campaign->sent_count;
        $failedCount = $this->campaign->failed_count;

        if ($sentCount > 0 && $failedCount === 0) {
            $this->campaign->markCompleted();
        } elseif ($sentCount === 0 && $failedCount > 0) {
            $this->campaign->markFailed();
        } elseif ($sentCount > 0) {
            // Some sent, some failed - still completed
            $this->campaign->markCompleted();
        } else {
            $this->campaign->markCompleted();
        }

        Log::info('Campaign finalized', [
            'campaign_id' => $this->campaign->id,
            'status' => $this->campaign->status,
            'sent' => $sentCount,
            'failed' => $failedCount,
        ]);
    }
}
