<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ListModel;
use App\Models\SmsCampaign;
use App\Models\SmsTemplate;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_returns_grouped_navigable_results(): void
    {
        $user = User::factory()->manager()->create();
        $contact = Contact::factory()->active()->create([
            'full_name' => 'Alpha Student',
            'first_name' => 'Alpha',
            'last_name' => 'Student',
            'phone_normalised' => '94770000001',
        ]);
        $campaign = SmsCampaign::factory()->create([
            'name' => 'Alpha Campaign',
            'status' => 'completed',
            'total_recipients' => 10,
            'sent_count' => 8,
            'failed_count' => 2,
            'created_by' => $user->id,
        ]);
        $template = SmsTemplate::factory()->create(['name' => 'Alpha Reminder']);
        $list = ListModel::factory()->create(['name' => 'Alpha List']);
        $tag = Tag::factory()->create(['name' => 'Alpha Tag']);

        activity()
            ->performedOn($campaign)
            ->causedBy($user)
            ->event('recipient_failed')
            ->withProperties(['name' => $campaign->name])
            ->log('Alpha recipient failed');

        $response = $this->actingAs($user)
            ->getJson('/global-search?q=Alpha');

        $response->assertOk()
            ->assertJsonPath('query', 'Alpha');

        $groups = collect($response->json('groups'));

        $this->assertSame(
            ['Contacts', 'Campaigns', 'Templates', 'Lists', 'Tags', 'Activity'],
            $groups->pluck('label')->all()
        );
        $this->assertSame(route('contacts.show', $contact), $groups->firstWhere('label', 'Contacts')['items'][0]['url']);
        $this->assertSame(route('campaigns.show', $campaign), $groups->firstWhere('label', 'Campaigns')['items'][0]['url']);
        $this->assertSame(route('templates.show', $template), $groups->firstWhere('label', 'Templates')['items'][0]['url']);
        $this->assertSame(route('lists.show', $list), $groups->firstWhere('label', 'Lists')['items'][0]['url']);
        $this->assertSame(route('tags.show', $tag), $groups->firstWhere('label', 'Tags')['items'][0]['url']);
    }

    public function test_global_search_requires_at_least_two_characters(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->getJson('/global-search?q=A')
            ->assertOk()
            ->assertJsonPath('groups', []);
    }
}
