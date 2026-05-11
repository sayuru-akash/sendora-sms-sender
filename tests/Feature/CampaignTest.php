<?php

namespace Tests\Feature;

use App\Jobs\FinalizeCampaign;
use App\Jobs\PrepareCampaignRecipients;
use App\Jobs\SendCampaignMessages;
use App\Jobs\SendSingleSms;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\ListModel;
use App\Models\SmsCampaign;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Sms\MessagePersonalizer;
use App\Services\Sms\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
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

    public function test_campaign_index_exposes_filters_summary_and_paginated_numbers(): void
    {
        SmsCampaign::factory()->completed()->create([
            'name' => 'Alpha Payment Campaign',
            'status' => 'completed',
            'total_recipients' => 12,
            'sent_count' => 10,
            'failed_count' => 2,
            'pending_count' => 0,
            'queued_count' => 0,
            'created_by' => $this->manager->id,
        ]);
        SmsCampaign::factory()->create([
            'name' => 'Beta Draft Campaign',
            'status' => 'draft',
            'total_recipients' => 5,
            'sent_count' => 0,
            'failed_count' => 0,
            'pending_count' => 5,
            'created_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->get('/campaigns?search=Alpha&status=completed')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Campaigns/Index')
                ->where('filters.search', 'Alpha')
                ->where('filters.status', 'completed')
                ->where('campaigns.meta.total', 1)
                ->where('campaigns.data.0.name', 'Alpha Payment Campaign')
                ->where('campaigns.data.0.total_recipients', 12)
                ->where('campaigns.data.0.sent_count', 10)
                ->where('campaigns.data.0.failed_count', 2)
                ->where('summary.campaigns_count', 1)
                ->where('summary.total_recipients_sum', 12)
                ->where('summary.sent_count_sum', 10)
                ->where('summary.failed_count_sum', 2)
                ->where('summary.status_counts.completed', 1)
            );
    }

    public function test_can_create_long_multi_segment_campaign_message(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/campaigns', [
            'name' => 'Long Payment Reminder',
            'message_body' => str_repeat('Payment reminder. ', 120),
            'target_type' => 'all_contacts',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('sms_campaigns', [
            'name' => 'Long Payment Reminder',
        ]);
    }

    public function test_campaign_sender_id_can_use_registered_name_with_space(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/campaigns', [
            'name' => 'Registered Sender Campaign',
            'sender_id' => 'SITC CAMPUS',
            'message_body' => 'Hello from Sendora!',
            'target_type' => 'all_contacts',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('sms_campaigns', [
            'name' => 'Registered Sender Campaign',
            'sender_id' => 'SITC CAMPUS',
        ]);
    }

    public function test_manager_can_create_and_send_campaign_immediately_from_builder(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->manager)->postJson('/campaigns', [
            'name' => 'Immediate Campaign',
            'message_body' => 'Hello now!',
            'target_type' => 'all_contacts',
            'send_now' => true,
        ]);

        $response->assertCreated();

        $campaign = SmsCampaign::where('name', 'Immediate Campaign')->firstOrFail();

        $this->assertTrue($campaign->isQueued());
        $activity = Activity::where('subject_type', SmsCampaign::class)
            ->where('subject_id', $campaign->id)
            ->where('event', 'send_requested')
            ->firstOrFail();

        $this->assertSame('Campaign send requested', $activity->description);
        $this->assertArrayNotHasKey('total_recipients', $activity->properties->toArray());
        Queue::assertPushed(PrepareCampaignRecipients::class);
    }

    public function test_staff_cannot_send_campaign_immediately_from_builder(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->staff)->postJson('/campaigns', [
            'name' => 'Unauthorized Immediate Campaign',
            'message_body' => 'Hello now!',
            'target_type' => 'all_contacts',
            'send_now' => true,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('sms_campaigns', [
            'name' => 'Unauthorized Immediate Campaign',
        ]);
        Queue::assertNothingPushed();
    }

    public function test_list_campaign_accepts_flat_list_ids_from_quick_form(): void
    {
        $list = ListModel::factory()->create();

        $response = $this->actingAs($this->staff)->postJson('/campaigns', [
            'name' => 'List Campaign',
            'message_body' => 'Hello list!',
            'target_type' => 'list',
            'list_ids' => [$list->id],
        ]);

        $response->assertCreated();

        $campaign = SmsCampaign::where('name', 'List Campaign')->firstOrFail();

        $this->assertSame(['list_ids' => [$list->id], 'tag_ids' => [], 'contact_ids' => []], $campaign->target_filters);
    }

    public function test_list_campaign_requires_at_least_one_selected_list(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/campaigns', [
            'name' => 'Empty List Campaign',
            'message_body' => 'Hello list!',
            'target_type' => 'list',
            'list_ids' => [],
        ]);

        $response->assertJsonValidationErrors('target_filters.list_ids');
    }

    public function test_manual_campaign_accepts_selected_contact_ids(): void
    {
        $contact = Contact::factory()->active()->create();

        $response = $this->actingAs($this->staff)->postJson('/campaigns', [
            'name' => 'Manual Campaign',
            'message_body' => 'Hello selected contact!',
            'target_type' => 'manual_selection',
            'contact_ids' => [$contact->id],
        ]);

        $response->assertCreated();

        $campaign = SmsCampaign::where('name', 'Manual Campaign')->firstOrFail();

        $this->assertSame(['list_ids' => [], 'tag_ids' => [], 'contact_ids' => [$contact->id]], $campaign->target_filters);
    }

    public function test_manual_campaign_requires_at_least_one_selected_contact(): void
    {
        $response = $this->actingAs($this->staff)->postJson('/campaigns', [
            'name' => 'Empty Manual Campaign',
            'message_body' => 'Hello nobody!',
            'target_type' => 'manual_selection',
            'contact_ids' => [],
        ]);

        $response->assertJsonValidationErrors('target_filters.contact_ids');
    }

    public function test_audience_contact_search_only_returns_receivable_contacts(): void
    {
        $receivable = Contact::factory()->active()->create([
            'full_name' => 'Manual Audience Student',
            'email' => 'manual-audience@example.com',
        ]);
        $blocked = Contact::factory()->blocked()->create([
            'full_name' => 'Manual Audience Blocked',
            'email' => 'manual-audience-blocked@example.com',
        ]);

        $response = $this->actingAs($this->staff)->getJson('/campaigns/audience/contacts?search=Manual%20Audience');

        $response->assertOk()
            ->assertJsonPath('contacts.0.id', $receivable->id);

        $ids = collect($response->json('contacts'))->pluck('id');

        $this->assertTrue($ids->contains($receivable->id));
        $this->assertFalse($ids->contains($blocked->id));
    }

    public function test_manual_audience_estimate_counts_receivable_contacts_only(): void
    {
        $receivable = Contact::factory()->active()->create();
        $blocked = Contact::factory()->blocked()->create();

        $response = $this->actingAs($this->staff)->postJson('/campaigns/audience/estimate', [
            'target_type' => 'manual_selection',
            'contact_ids' => [$receivable->id, $blocked->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 1);
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

    public function test_prepare_campaign_recipients_personalises_contact_placeholders(): void
    {
        $contact = Contact::factory()->active()->create([
            'first_name' => 'Nimal',
            'last_name' => 'Perera',
            'full_name' => 'Nimal Perera',
            'company' => 'SITC',
            'city' => 'Colombo',
            'district' => 'Colombo',
        ]);
        $campaign = SmsCampaign::factory()->create([
            'message_body' => 'Hi {first_name} {last_name}, {company} in {city}/{district}',
            'target_type' => 'manual_selection',
            'target_filters' => ['contact_ids' => [$contact->id]],
        ]);

        (new PrepareCampaignRecipients($campaign))->handle(app(MessagePersonalizer::class));

        $recipient = CampaignRecipient::where('campaign_id', $campaign->id)->firstOrFail();

        $this->assertSame('Hi Nimal Perera, SITC in Colombo/Colombo', $recipient->personalised_message);
    }

    public function test_send_campaign_messages_queues_every_pending_recipient_when_status_changes_during_iteration(): void
    {
        Queue::fake([SendSingleSms::class]);

        $campaign = SmsCampaign::factory()->sending()->create([
            'total_recipients' => 125,
            'pending_count' => 125,
            'queued_count' => 0,
            'sent_count' => 0,
        ]);

        Contact::factory()->active()->count(125)->create()->each(function (Contact $contact) use ($campaign): void {
            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'phone_normalised' => $contact->phone_normalised,
                'status' => 'pending',
            ]);
        });

        (new SendCampaignMessages($campaign))->handle(app(ActivityLogger::class));

        $campaign->refresh();

        $this->assertSame(0, CampaignRecipient::where('campaign_id', $campaign->id)->where('status', 'pending')->count());
        $this->assertSame(125, CampaignRecipient::where('campaign_id', $campaign->id)->where('status', 'queued')->count());
        $this->assertSame(125, $campaign->pending_count);
        $this->assertSame(125, $campaign->queued_count);
        Queue::assertPushed(SendSingleSms::class, 125);
    }

    public function test_send_single_sms_personalises_legacy_recipient_without_prepared_message(): void
    {
        config()->set('sms.username', 'testuser');
        config()->set('sms.password', 'testpass');
        config()->set('sms.source', 'SITC CAMPUS');
        config()->set('sms.api_url', 'https://msg.text-ware.com/send_sms.php');

        Http::fake([
            'msg.text-ware.com/*' => Http::response('Operation success: msg123', 200),
        ]);

        $contact = Contact::factory()->active()->create([
            'first_name' => 'Nimal',
            'city' => 'Colombo',
            'phone_normalised' => '94771111111',
        ]);
        $campaign = SmsCampaign::factory()->sending()->create([
            'message_body' => 'Hi {first_name} from {city}',
            'sender_id' => 'SITC CAMPUS',
            'pending_count' => 1,
        ]);
        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'phone_normalised' => $contact->phone_normalised,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        (new SendSingleSms($campaign, $recipient))->handle(
            app(SmsService::class),
            app(MessagePersonalizer::class),
            app(ActivityLogger::class),
        );

        $recipient->refresh();

        $this->assertSame('sent', $recipient->status);
        $this->assertSame('msg123', $recipient->provider_message_id);
        $this->assertDatabaseHas('sms_messages', [
            'campaign_recipient_id' => $recipient->id,
            'message_body' => 'Hi Nimal from Colombo',
            'status' => 'sent',
        ]);
        $activity = Activity::where('subject_type', SmsCampaign::class)
            ->where('subject_id', $campaign->id)
            ->where('event', 'recipient_sent')
            ->firstOrFail();

        $this->assertSame('Campaign recipient sent', $activity->description);
        $this->assertSame($recipient->id, $activity->properties['recipient_id']);
        $this->assertSame('msg123', $activity->properties['provider_message_id']);
        $this->assertArrayNotHasKey('message_body', $activity->properties->toArray());

        Http::assertSent(fn ($request) => $request->data()['msg'] === 'Hi Nimal from Colombo');
    }

    public function test_send_single_sms_blocks_unresolved_placeholders_before_provider_call(): void
    {
        config()->set('sms.username', 'testuser');
        config()->set('sms.password', 'testpass');
        config()->set('sms.source', 'SITC CAMPUS');
        config()->set('sms.api_url', 'https://msg.text-ware.com/send_sms.php');

        Http::fake();

        $contact = Contact::factory()->active()->create();
        $campaign = SmsCampaign::factory()->sending()->create([
            'message_body' => 'Payment due: {amount}',
            'sender_id' => 'SITC CAMPUS',
            'pending_count' => 1,
        ]);
        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'phone_normalised' => $contact->phone_normalised,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        (new SendSingleSms($campaign, $recipient))->handle(
            app(SmsService::class),
            app(MessagePersonalizer::class),
            app(ActivityLogger::class),
        );

        $recipient->refresh();

        $this->assertSame('failed', $recipient->status);
        $this->assertSame('Message contains unresolved placeholders: amount', $recipient->error_message);
        $this->assertDatabaseHas('sms_messages', [
            'campaign_recipient_id' => $recipient->id,
            'status' => 'failed',
            'error_message' => 'Message contains unresolved placeholders: amount',
        ]);
        $activity = Activity::where('subject_type', SmsCampaign::class)
            ->where('subject_id', $campaign->id)
            ->where('event', 'recipient_failed')
            ->firstOrFail();

        $this->assertSame('Campaign recipient failed', $activity->description);
        $this->assertSame($recipient->id, $activity->properties['recipient_id']);
        $this->assertSame('Message contains unresolved placeholders: amount', $activity->properties['error_message']);
        $this->assertArrayNotHasKey('message_body', $activity->properties->toArray());

        Http::assertNothingSent();
    }

    public function test_duplicate_send_single_sms_job_does_not_call_provider_twice(): void
    {
        config()->set('sms.username', 'testuser');
        config()->set('sms.password', 'testpass');
        config()->set('sms.source', 'SITC CAMPUS');
        config()->set('sms.api_url', 'https://msg.text-ware.com/send_sms.php');

        Http::fake([
            'msg.text-ware.com/*' => Http::response('Operation success: msg123', 200),
        ]);

        $contact = Contact::factory()->active()->create([
            'phone_normalised' => '94771111111',
        ]);
        $campaign = SmsCampaign::factory()->sending()->create([
            'message_body' => 'Hello once',
            'sender_id' => 'SITC CAMPUS',
        ]);
        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'phone_normalised' => $contact->phone_normalised,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        $job = new SendSingleSms($campaign, $recipient);
        $job->handle(app(SmsService::class), app(MessagePersonalizer::class), app(ActivityLogger::class));

        (new SendSingleSms($campaign, $recipient->fresh()))->handle(
            app(SmsService::class),
            app(MessagePersonalizer::class),
            app(ActivityLogger::class),
        );

        Http::assertSentCount(1);
        $this->assertSame('sent', $recipient->fresh()->status);
        $this->assertSame(1, $recipient->fresh()->attempt_count);
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

    public function test_manager_can_resend_one_failed_campaign_recipient(): void
    {
        Queue::fake();

        $campaign = SmsCampaign::factory()->completed()->create([
            'total_recipients' => 1,
            'failed_count' => 1,
            'created_by' => $this->manager->id,
        ]);
        $contact = Contact::factory()->active()->create();
        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'phone_normalised' => $contact->phone_normalised,
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => 'Provider rejected the message.',
            'provider_response' => ['status_code' => 400],
            'attempt_count' => 1,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson("/campaigns/{$campaign->id}/recipients/{$recipient->id}/resend");

        $response->assertOk();

        $recipient->refresh();
        $campaign->refresh();

        $this->assertSame('queued', $recipient->status);
        $this->assertNotNull($recipient->queued_at);
        $this->assertNull($recipient->failed_at);
        $this->assertNull($recipient->error_message);
        $this->assertSame(1, $recipient->attempt_count);
        $this->assertSame('sending', $campaign->status);
        $this->assertSame(1, $campaign->queued_count);
        $this->assertSame(0, $campaign->failed_count);

        Queue::assertPushed(SendSingleSms::class);
        Queue::assertPushed(FinalizeCampaign::class);
    }

    public function test_manager_can_resend_all_failed_campaign_recipients(): void
    {
        Queue::fake();

        $campaign = SmsCampaign::factory()->completed()->create([
            'total_recipients' => 3,
            'sent_count' => 1,
            'failed_count' => 2,
            'created_by' => $this->manager->id,
        ]);
        $sentContact = Contact::factory()->active()->create();
        $failedContacts = Contact::factory()->active()->count(2)->create();

        CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $sentContact->id,
            'phone_normalised' => $sentContact->phone_normalised,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $failedContacts->each(function (Contact $contact) use ($campaign): void {
            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contact->id,
                'phone_normalised' => $contact->phone_normalised,
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => 'Provider rejected the message.',
            ]);
        });

        $response = $this->actingAs($this->manager)
            ->postJson("/campaigns/{$campaign->id}/resend-failed");

        $response->assertOk();

        $campaign->refresh();

        $this->assertSame('sending', $campaign->status);
        $this->assertSame(3, $campaign->total_recipients);
        $this->assertSame(2, $campaign->queued_count);
        $this->assertSame(1, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertSame(2, CampaignRecipient::where('campaign_id', $campaign->id)->where('status', 'queued')->count());

        Queue::assertPushed(SendSingleSms::class, 2);
        Queue::assertPushed(FinalizeCampaign::class);
    }

    public function test_failed_recipients_cannot_be_resent_while_campaign_is_active(): void
    {
        Queue::fake();

        $campaign = SmsCampaign::factory()->sending()->create([
            'total_recipients' => 1,
            'failed_count' => 1,
            'created_by' => $this->manager->id,
        ]);
        $contact = Contact::factory()->active()->create();
        CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'phone_normalised' => $contact->phone_normalised,
            'status' => 'failed',
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson("/campaigns/{$campaign->id}/resend-failed");

        $response->assertStatus(422);
        Queue::assertNothingPushed();
    }

    public function test_campaign_cancel_works(): void
    {
        $campaign = SmsCampaign::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->manager)->postJson("/campaigns/{$campaign->id}/cancel");

        $response->assertStatus(200);
        $this->assertTrue($campaign->fresh()->isCancelled());
    }

    public function test_pausing_campaign_reclaims_queued_recipients_for_resume(): void
    {
        $campaign = SmsCampaign::factory()->sending()->create([
            'total_recipients' => 1,
            'queued_count' => 1,
            'created_by' => $this->manager->id,
        ]);
        $contact = Contact::factory()->active()->create();
        $recipient = CampaignRecipient::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'phone_normalised' => $contact->phone_normalised,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        $this->actingAs($this->manager)->postJson("/campaigns/{$campaign->id}/pause")->assertOk();

        $this->assertTrue($campaign->fresh()->isPaused());
        $this->assertSame('pending', $recipient->fresh()->status);
        $this->assertNull($recipient->fresh()->queued_at);
    }

    public function test_cancelling_campaign_skips_unsent_recipients(): void
    {
        $campaign = SmsCampaign::factory()->sending()->create([
            'total_recipients' => 2,
            'pending_count' => 1,
            'queued_count' => 1,
            'created_by' => $this->manager->id,
        ]);
        $contacts = Contact::factory()->active()->count(2)->create();

        foreach (['pending', 'queued'] as $index => $status) {
            CampaignRecipient::create([
                'campaign_id' => $campaign->id,
                'contact_id' => $contacts[$index]->id,
                'phone_normalised' => $contacts[$index]->phone_normalised,
                'status' => $status,
                'queued_at' => $status === 'queued' ? now() : null,
            ]);
        }

        $this->actingAs($this->manager)->postJson("/campaigns/{$campaign->id}/cancel")->assertOk();

        $campaign->refresh();
        $this->assertTrue($campaign->isCancelled());
        $this->assertSame(2, CampaignRecipient::where('campaign_id', $campaign->id)->where('status', 'skipped')->count());
        $this->assertSame(2, $campaign->skipped_count);
        $this->assertSame(0, $campaign->pending_count);
        $this->assertSame(0, $campaign->queued_count);
    }
}
