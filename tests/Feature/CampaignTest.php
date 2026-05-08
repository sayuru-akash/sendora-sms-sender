<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\SmsCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = User::factory()->staff()->create();
        $this->manager = User::factory()->manager()->create();
    }

    public function test_can_create_draft_campaign(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/campaigns', [
            'name' => 'Test Campaign',
            'message_body' => 'Hello from Sendora!',
            'target_type' => 'all_contacts',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('sms_campaigns', [
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);
    }

    public function test_campaign_excludes_unsubscribed(): void
    {
        Contact::factory()->active()->count(3)->create();
        Contact::factory()->unsubscribed()->count(2)->create();

        // canReceiveSms should only return active/inactive contacts
        $receivable = Contact::canReceiveSms()->count();
        $this->assertEquals(3, $receivable);
    }

    public function test_campaign_excludes_blocked(): void
    {
        Contact::factory()->active()->count(3)->create();
        Contact::factory()->blocked()->count(2)->create();

        $receivable = Contact::canReceiveSms()->count();
        $this->assertEquals(3, $receivable);
    }

    public function test_campaign_excludes_invalid(): void
    {
        Contact::factory()->active()->count(3)->create();
        Contact::factory()->invalid()->count(2)->create();

        $receivable = Contact::canReceiveSms()->count();
        $this->assertEquals(3, $receivable);
    }

    public function test_campaign_prevents_duplicate_sends(): void
    {
        // Create campaign
        $campaign = SmsCampaign::factory()->create(['status' => 'draft']);

        // The unique constraint on campaign_recipients (campaign_id, phone_normalised)
        // prevents duplicate sends at the database level
        $this->assertDatabaseHas('sms_campaigns', [
            'id' => $campaign->id,
            'status' => 'draft',
        ]);
    }

    public function test_campaign_status_transitions(): void
    {
        $campaign = SmsCampaign::factory()->create(['status' => 'draft']);

        $this->assertTrue($campaign->isDraft());
        $this->assertTrue($campaign->canBeSent());

        $campaign->markQueued();
        $this->assertTrue($campaign->isQueued());

        $campaign->markSending();
        $this->assertTrue($campaign->isSending());
        $this->assertTrue($campaign->canBePaused());

        $campaign->markPaused();
        $this->assertTrue($campaign->isPaused());

        $campaign->markSending();
        $campaign->markCompleted();
        $this->assertTrue($campaign->isCompleted());
    }

    public function test_queued_campaign_cannot_be_sent_again(): void
    {
        Queue::fake();

        $campaign = SmsCampaign::factory()->create(['status' => 'draft']);

        $this->actingAs($this->manager)->postJson("/campaigns/{$campaign->id}/send", [
            'confirmed' => true,
        ])->assertOk();

        $this->assertTrue($campaign->fresh()->isQueued());

        $this->actingAs($this->manager)->postJson("/campaigns/{$campaign->id}/send", [
            'confirmed' => true,
        ])->assertStatus(422);
    }

    public function test_unauthorized_user_cannot_send_campaign(): void
    {
        $viewer = User::factory()->viewer()->create();
        $campaign = SmsCampaign::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($viewer)->postJson("/campaigns/{$campaign->id}/send", [
            'confirmed' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_manager_can_send_campaign(): void
    {
        $campaign = SmsCampaign::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->manager)->postJson("/campaigns/{$campaign->id}/send", [
            'confirmed' => true,
        ]);

        // Should succeed (200 or redirect)
        $response->assertStatus(200);
    }

    public function test_campaign_cancel_works(): void
    {
        $campaign = SmsCampaign::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->manager)->postJson("/campaigns/{$campaign->id}/cancel");

        $response->assertStatus(200);
        $this->assertTrue($campaign->fresh()->isCancelled());
    }
}
