<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_and_admin_can_view_settings(): void
    {
        $this->actingAs(User::factory()->owner()->create())->get('/settings')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/settings')->assertOk();

        foreach ([User::factory()->manager()->create(), User::factory()->staff()->create(), User::factory()->viewer()->create()] as $user) {
            $this->actingAs($user)->get('/settings')->assertForbidden();
        }
    }

    public function test_manager_cannot_send_test_sms_from_settings(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)->postJson('/settings/test-sms', [
            'phone' => '0771234567',
            'message' => 'Test message',
        ])->assertForbidden();
    }

    public function test_settings_update_rejects_unknown_legacy_keys(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->putJson('/settings', [
            'settings' => [
                [
                    'key' => 'sms.password',
                    'value' => 'secret',
                    'type' => 'string',
                    'group' => 'sms',
                ],
            ],
        ])->assertJsonValidationErrors('settings.0.key');

        $this->assertDatabaseMissing('system_settings', [
            'key' => 'sms.password',
        ]);
    }

    public function test_owner_can_update_whitelisted_settings(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->putJson('/settings', [
            'company_name' => 'SITC Campus',
            'max_import_file_size' => 20,
            'default_duplicate_handling' => 'update',
        ])->assertOk();

        $this->assertSame('SITC Campus', SystemSetting::get('company_name'));
        $this->assertSame(20, SystemSetting::get('max_import_file_size'));
        $this->assertSame('update', SystemSetting::get('default_duplicate_handling'));
    }

    public function test_inertia_settings_update_redirects_back_with_flash(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->from('/settings')
            ->put('/settings', [
                'company_name' => 'SITC Campus',
                'timezone' => 'Asia/Colombo',
                'date_format' => 'd/m/Y',
                'default_country_code' => '+94',
            ])
            ->assertRedirect('/settings')
            ->assertSessionHas('success', 'Settings updated successfully.');

        $this->assertSame('SITC Campus', SystemSetting::get('company_name'));
    }
}
