<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@sendora.com'],
            [
                'name' => 'Owner',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $settings = [
            ['key' => 'company_name', 'value' => 'Sendora', 'type' => 'string', 'group' => 'general'],
            ['key' => 'timezone', 'value' => 'Asia/Colombo', 'type' => 'string', 'group' => 'general'],
            ['key' => 'default_country_code', 'value' => '94', 'type' => 'string', 'group' => 'general'],
            ['key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string', 'group' => 'general'],
            ['key' => 'items_per_page', 'value' => '25', 'type' => 'integer', 'group' => 'general'],
            ['key' => 'max_import_size_mb', 'value' => '10', 'type' => 'integer', 'group' => 'imports'],
            ['key' => 'auto_assign_import_source', 'value' => 'import', 'type' => 'string', 'group' => 'imports'],
            ['key' => 'default_duplicate_handling', 'value' => 'skip', 'type' => 'string', 'group' => 'imports'],
            ['key' => 'sms_rate_limit_per_minute', 'value' => '300', 'type' => 'integer', 'group' => 'sms'],
            ['key' => 'sms_timeout_seconds', 'value' => '30', 'type' => 'integer', 'group' => 'sms'],
            ['key' => 'sms_provider', 'value' => 'textware', 'type' => 'string', 'group' => 'sms'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::set($setting['key'], $setting['value'], $setting['type'], $setting['group']);
        }
    }
}
