<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_admin_user(): void
    {
        $owner = User::factory()->owner()->create();

        $response = $this->actingAs($owner)->postJson('/users', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_admin_cannot_create_admin_or_owner_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/users', [
            'name' => 'Escalated User',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['role']);
        $this->assertDatabaseMissing('users', [
            'email' => 'owner@example.com',
        ]);
    }

    public function test_admin_can_create_manager_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/users', [
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'email' => 'manager@example.com',
            'role' => 'manager',
        ]);
    }

    public function test_owner_can_search_users_case_insensitively(): void
    {
        $owner = User::factory()->owner()->create();
        User::factory()->create([
            'name' => 'Searchable Manager',
            'email' => 'searchable.manager@example.com',
            'role' => 'manager',
        ]);

        $response = $this->actingAs($owner)->get('/users?search=MANAGER');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->where('users.0.name', 'Searchable Manager')
            );
    }

    public function test_user_search_stays_scoped_to_role_filter(): void
    {
        $owner = User::factory()->owner()->create();
        $manager = User::factory()->manager()->create([
            'name' => 'Filtered Search Person',
            'email' => 'filtered-manager@example.com',
        ]);
        User::factory()->admin()->create([
            'name' => 'Filtered Search Person',
            'email' => 'filtered-admin@example.com',
        ]);

        $response = $this->actingAs($owner)->get('/users?search=filtered&role=manager');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->where('users.0.id', $manager->id)
                ->has('users', 1)
            );
    }

    public function test_admin_cannot_update_owner_or_self(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();

        $ownerResponse = $this->actingAs($admin)->putJson("/users/{$owner->id}", [
            'name' => 'Changed Owner',
            'email' => $owner->email,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $selfResponse = $this->actingAs($admin)->putJson("/users/{$admin->id}", [
            'name' => 'Changed Admin',
            'email' => $admin->email,
            'role' => 'manager',
            'status' => 'active',
        ]);

        $ownerResponse->assertForbidden();
        $selfResponse->assertForbidden();
    }
}
