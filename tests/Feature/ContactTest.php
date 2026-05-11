<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ListModel;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->staff()->create();
    }

    public function test_can_create_contact(): void
    {
        $response = $this->actingAs($this->user)->postJson('/contacts', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0771234567',
            'email' => 'john@example.com',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_normalised' => '94771234567',
        ]);
    }

    public function test_can_create_contact_with_lists_and_tags(): void
    {
        $list = ListModel::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/contacts', [
            'first_name' => 'List',
            'last_name' => 'Tagged',
            'phone' => '0771234567',
            'status' => 'active',
            'lists' => [$list->id],
            'tags' => [$tag->id],
        ]);

        $response->assertStatus(201);

        $contact = Contact::where('phone_normalised', '94771234567')->firstOrFail();

        $this->assertTrue($contact->lists()->where('lists.id', $list->id)->exists());
        $this->assertTrue($contact->tags()->where('tags.id', $tag->id)->exists());
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'event' => 'created',
        ]);
    }

    public function test_phone_is_normalized_on_create(): void
    {
        $this->actingAs($this->user)->postJson('/contacts', [
            'first_name' => 'Jane',
            'phone' => '0771234567',
        ]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Jane',
            'phone_normalised' => '94771234567',
        ]);

        // Test with +94 format
        $this->actingAs($this->user)->postJson('/contacts', [
            'first_name' => 'Bob',
            'phone' => '+94771234568',
        ]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Bob',
            'phone_normalised' => '94771234568',
        ]);
    }

    public function test_duplicate_phone_rejected(): void
    {
        Contact::factory()->create(['phone_normalised' => '94771234567']);

        $response = $this->actingAs($this->user)->postJson('/contacts', [
            'first_name' => 'Duplicate',
            'phone' => '0771234567',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    public function test_can_list_contacts(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->getJson('/contacts');

        $response->assertStatus(200);
    }

    public function test_can_filter_by_status(): void
    {
        Contact::factory()->count(3)->active()->create();
        Contact::factory()->count(2)->inactive()->create();

        $response = $this->actingAs($this->user)->getJson('/contacts?status=active');

        $response->assertStatus(200);
    }

    public function test_can_search_contacts(): void
    {
        Contact::factory()->create(['full_name' => 'John Doe', 'phone_normalised' => '94771111111']);
        Contact::factory()->create(['full_name' => 'Jane Smith', 'phone_normalised' => '94772222222']);

        $response = $this->actingAs($this->user)->getJson('/contacts?search=John');

        $response->assertStatus(200);
    }

    public function test_can_bulk_tag_contacts(): void
    {
        $contacts = Contact::factory()->count(3)->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/contacts/bulk-action', [
            'action' => 'tag',
            'contact_ids' => $contacts->pluck('id')->toArray(),
            'tag_ids' => [$tag->id],
        ]);

        $response->assertStatus(200);

        foreach ($contacts as $contact) {
            $this->assertTrue($contact->fresh()->tags()->where('tags.id', $tag->id)->exists());
        }
    }

    public function test_can_bulk_add_to_list(): void
    {
        $contacts = Contact::factory()->count(3)->create();
        $list = ListModel::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/contacts/bulk-action', [
            'action' => 'add_to_list',
            'contact_ids' => $contacts->pluck('id')->toArray(),
            'list_id' => $list->id,
        ]);

        $response->assertStatus(200);

        foreach ($contacts as $contact) {
            $this->assertTrue($contact->fresh()->lists()->where('lists.id', $list->id)->exists());
        }
    }

    public function test_unsubscribed_contact_excluded_from_campaigns(): void
    {
        $activeContact = Contact::factory()->active()->create();
        $unsubscribedContact = Contact::factory()->unsubscribed()->create();

        // The canReceiveSms scope should exclude unsubscribed
        $canReceive = Contact::canReceiveSms()->pluck('id');

        $this->assertTrue($canReceive->contains($activeContact->id));
        $this->assertFalse($canReceive->contains($unsubscribedContact->id));
    }
}
