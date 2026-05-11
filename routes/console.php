<?php

use App\Jobs\FinalizeCampaign;
use App\Jobs\PrepareCampaignRecipients;
use App\Jobs\SendCampaignMessages;
use App\Models\SmsCampaign;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process scheduled campaigns every minute
Schedule::call(function () {
    $dueCampaigns = SmsCampaign::due()->get();

    foreach ($dueCampaigns as $campaign) {
        $claimed = SmsCampaign::whereKey($campaign->id)
            ->where('status', 'scheduled')
            ->update([
                'status' => 'queued',
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            continue;
        }

        $campaign->refresh();

        PrepareCampaignRecipients::dispatch($campaign)
            ->chain([
                new SendCampaignMessages($campaign),
                new FinalizeCampaign($campaign),
            ]);
    }
})
    ->everyMinute()
    ->name('process-scheduled-campaigns')
    ->withoutOverlapping()
    ->onOneServer();
