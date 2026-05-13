<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_renders_empty_dataset_on_sqlite(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/reports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('stats.total_contacts', 0)
                ->where('stats.total_sms_sent', 0)
                ->where('stats.total_campaigns', 0)
                ->where('stats.avg_success_rate', 0)
                ->where('sms_over_time', [])
                ->where('contacts_by_source', [])
                ->where('contacts_by_status', [])
                ->where('top_lists', [])
                ->where('top_tags', [])
            );
    }

    public function test_reports_index_groups_sms_by_month_without_postgres_only_sql(): void
    {
        $manager = User::factory()->manager()->create();
        Contact::factory()->active()->create([
            'source' => 'CCB - 26.1 students',
        ]);
        SmsMessage::create([
            'phone_normalised' => '94770000001',
            'message_body' => 'Sent report message',
            'provider' => 'textware',
            'status' => 'sent',
            'sent_at' => now()->setDate(2026, 5, 13)->setTime(8, 15),
        ]);
        SmsMessage::create([
            'phone_normalised' => '94770000002',
            'message_body' => 'Failed report message',
            'provider' => 'textware',
            'status' => 'failed',
            'failed_at' => now()->setDate(2026, 5, 14)->setTime(9, 20),
        ]);

        $this->actingAs($manager)
            ->get('/reports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('stats.total_contacts', 1)
                ->where('stats.total_sms_sent', 1)
                ->where('sms_over_time.0.month', '2026-05')
                ->where('sms_over_time.0.sent', 1)
                ->where('sms_over_time.0.failed', 1)
                ->where('contacts_by_source.0.source', 'CCB - 26.1 students')
                ->where('contacts_by_status.0.status', 'active')
            );
    }
}
