<?php

namespace Tests\Feature;

use App\Models\SmsCampaign;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_exposes_subject_action_url_and_properties(): void
    {
        $user = User::factory()->manager()->create();
        $campaign = SmsCampaign::factory()->create([
            'name' => 'Retry Audit Campaign',
            'created_by' => $user->id,
        ]);

        $activity = activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->event('resend_queued')
            ->withProperties([
                'name' => $campaign->name,
                'recipient_count' => 2,
            ])
            ->log('Campaign failed recipients resend queued');

        $this->actingAs($user)
            ->get('/activity-logs?event=resend_queued')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ActivityLogs/Index')
                ->where('activities.data.0.event', 'resend_queued')
                ->where('activities.data.0.subject_name', 'Retry Audit Campaign')
                ->where('activities.data.0.subject_url', route('campaigns.show', ['campaign' => $campaign, 'activity_id' => $activity->id]).'#activity')
                ->where('activities.data.0.subject_action_label', 'Open campaign')
                ->where('activities.data.0.properties.recipient_count', 2)
            );
    }

    public function test_activity_log_can_be_filtered_to_one_campaign(): void
    {
        $user = User::factory()->manager()->create();
        $campaign = SmsCampaign::factory()->create([
            'name' => 'Filtered Campaign',
            'created_by' => $user->id,
        ]);
        $otherCampaign = SmsCampaign::factory()->create([
            'name' => 'Other Campaign',
            'created_by' => $user->id,
        ]);

        activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->event('completed')
            ->withProperties(['name' => $campaign->name])
            ->log('Campaign completed');
        activity()
            ->performedOn($otherCampaign)
            ->causedBy($user)
            ->event('completed')
            ->withProperties(['name' => $otherCampaign->name])
            ->log('Other campaign completed');

        $this->actingAs($user)
            ->get('/activity-logs?subject_type=App%5CModels%5CSmsCampaign&subject_id='.$campaign->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ActivityLogs/Index')
                ->where('activities.meta.total', 2)
                ->where('activities.data.0.subject_id', $campaign->id)
                ->where('activities.data.1.subject_id', $campaign->id)
            );
    }

    public function test_campaign_show_includes_recent_activity_and_activity_log_link_context(): void
    {
        $user = User::factory()->manager()->create();
        $campaign = SmsCampaign::factory()->create([
            'name' => 'Campaign With Activity',
            'created_by' => $user->id,
        ]);

        $activity = activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->event('completed')
            ->withProperties(['name' => $campaign->name])
            ->log('Campaign completed');

        $this->actingAs($user)
            ->get(route('campaigns.show', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Campaigns/Show')
                ->where('recent_activities.0.event', 'completed')
                ->where('recent_activities.0.subject_url', route('campaigns.show', ['campaign' => $campaign, 'activity_id' => $activity->id]).'#activity')
            );
    }

    public function test_campaign_status_activity_logs_are_recorded(): void
    {
        $user = User::factory()->manager()->create();
        $campaign = SmsCampaign::factory()->completed()->create([
            'name' => 'Status Audit Campaign',
            'created_by' => $user->id,
            'total_recipients' => 1,
            'sent_count' => 1,
        ]);

        $logger = app(ActivityLogger::class);
        $logger->logCampaignCompleted($campaign);

        $activity = Activity::where('subject_type', SmsCampaign::class)
            ->where('subject_id', $campaign->id)
            ->where('event', 'completed')
            ->firstOrFail();

        $this->assertSame('Campaign completed', $activity->description);
        $this->assertSame('completed', $activity->properties['status']);
        $this->assertSame(1, $activity->properties['sent_count']);
        $this->assertSame('Asia/Colombo', $activity->properties['timezone']);
    }

    public function test_activity_log_search_includes_properties_and_causer(): void
    {
        $user = User::factory()->manager()->create([
            'name' => 'Audit Owner',
        ]);
        $campaign = SmsCampaign::factory()->create([
            'name' => 'Property Search Campaign',
            'created_by' => $user->id,
        ]);

        activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->event('recipient_failed')
            ->withProperties([
                'name' => $campaign->name,
                'error_message' => 'Provider timeout reference ZX-900',
            ])
            ->log('Campaign recipient failed');

        $this->actingAs($user)
            ->get('/activity-logs?search=ZX-900')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ActivityLogs/Index')
                ->where('activities.meta.total', 1)
                ->where('activities.data.0.properties.error_message', 'Provider timeout reference ZX-900')
            );

        $this->actingAs($user)
            ->get('/activity-logs?search=Audit%20Owner')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('activities.meta.total', 1)
                ->where('activities.data.0.causer_name', 'Audit Owner')
            );
    }
}
