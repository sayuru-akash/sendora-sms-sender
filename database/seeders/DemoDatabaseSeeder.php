<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\ListModel;
use App\Models\SmsCampaign;
use App\Models\SmsTemplate;
use App\Models\SystemSetting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@sendora.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@sendora.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@sendora.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@sendora.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $viewer = User::create([
            'name' => 'Viewer',
            'email' => 'viewer@sendora.com',
            'password' => Hash::make('password'),
            'role' => 'viewer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Tags
        $tags = [
            ['name' => 'VIP', 'colour' => '#ef4444', 'description' => 'Very important contacts'],
            ['name' => 'Lead', 'colour' => '#f97316', 'description' => 'Sales leads'],
            ['name' => 'Customer', 'colour' => '#22c55e', 'description' => 'Active customers'],
            ['name' => 'Student', 'colour' => '#3b82f6', 'description' => 'Student contacts'],
            ['name' => 'Event Attendee', 'colour' => '#8b5cf6', 'description' => 'People who attended events'],
            ['name' => 'Hot Lead', 'colour' => '#dc2626', 'description' => 'Hot leads ready to convert'],
            ['name' => 'Follow Up', 'colour' => '#eab308', 'description' => 'Needs follow up'],
        ];

        $tagModels = [];
        foreach ($tags as $tagData) {
            $tagModels[] = Tag::create([
                ...$tagData,
                'slug' => Str::slug($tagData['name']),
                'created_by' => $owner->id,
            ]);
        }

        // Lists
        $lists = [
            ['name' => 'Website Leads', 'colour' => '#6366f1', 'description' => 'Leads from the website contact form'],
            ['name' => 'Event Registrations', 'colour' => '#ec4899', 'description' => 'People who registered for events'],
            ['name' => 'CCA Leads', 'colour' => '#14b8a6', 'description' => 'Leads from CCA channel'],
            ['name' => 'Existing Customers', 'colour' => '#f59e0b', 'description' => 'Current active customers'],
        ];

        $listModels = [];
        foreach ($lists as $listData) {
            $listModels[] = ListModel::create([
                ...$listData,
                'slug' => Str::slug($listData['name']),
                'status' => 'active',
                'created_by' => $owner->id,
            ]);
        }

        // 50 sample contacts with Sri Lankan phone numbers
        $districts = ['Colombo', 'Gampaha', 'Kandy', 'Galle', 'Matara', 'Jaffna', 'Kurunegala', 'Anuradhapura', 'Ratnapura', 'Badulla'];
        $firstNames = ['Amal', 'Kamal', 'Nimal', 'Sunil', 'Chamari', 'Kumari', 'Nisha', 'Dinesh', 'Roshan', 'Pradeep', 'Sanjeewa', 'Thilini', 'Madushi', 'Kasun', 'Dilshan', 'Hashini', 'Nuwan', 'Sachini', 'Buddhika', 'Charith', 'Ishara', 'Janaka', 'Lakmal', 'Manoj', 'Nadeesha', 'Oshadhi', 'Pasan', 'Ravindu', 'Sanduni', 'Tharaka', 'Udara', 'Vimukthi', 'Wasana', 'Yasiru', 'Zeenath', 'Ashan', 'Bimsara', 'Chathura', 'Dulani', 'Eranga', 'Fathima', 'Gayani', 'Hiruni', 'Indika', 'Jeevani', 'Kavindu', 'Lahiru', 'Malsha', 'Nipun', 'Oshini'];

        $contacts = [];
        $usedPhones = [];

        for ($i = 0; $i < 50; $i++) {
            // Generate unique phone number
            do {
                $phoneDigits = '7'.mt_rand(0, 1).str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            } while (in_array($phoneDigits, $usedPhones));
            $usedPhones[] = $phoneDigits;

            $contacts[] = Contact::create([
                'uuid' => Str::uuid(),
                'first_name' => $firstNames[$i],
                'last_name' => 'Perera',
                'full_name' => $firstNames[$i].' Perera',
                'phone' => '0'.$phoneDigits,
                'phone_normalised' => '94'.$phoneDigits,
                'email' => strtolower($firstNames[$i]).'@example.com',
                'company' => fake()->optional(0.5)->company(),
                'job_title' => fake()->optional(0.4)->jobTitle(),
                'country' => 'Sri Lanka',
                'district' => $districts[array_rand($districts)],
                'city' => fake()->optional(0.6)->city(),
                'gender' => $i % 3 === 0 ? 'female' : 'male',
                'source' => $i < 20 ? 'manual' : ($i < 35 ? 'import' : 'web_form'),
                'status' => 'active',
                'notes' => fake()->optional(0.2)->sentence(),
                'created_by' => $owner->id,
                'updated_by' => $owner->id,
            ]);
        }

        // Assign contacts to lists and tags randomly
        foreach ($contacts as $contact) {
            // Assign 1-2 random tags
            $randomTags = collect($tagModels)->random(rand(1, 2))->pluck('id')->toArray();
            $contact->tags()->sync($randomTags);

            // Assign to 1 random list
            $randomList = $listModels[array_rand($listModels)];
            $contact->lists()->syncWithoutDetaching([$randomList->id]);
        }

        // SMS Templates
        $templates = [
            [
                'name' => 'Welcome Message',
                'category' => 'notification',
                'body' => 'Hi {first_name}, welcome to Sendora! We are excited to have you on board.',
                'variables' => ['first_name'],
                'status' => 'active',
            ],
            [
                'name' => 'Follow Up',
                'category' => 'marketing',
                'body' => 'Hi {first_name}, just following up on our recent conversation. Please let us know if you have any questions.',
                'variables' => ['first_name'],
                'status' => 'active',
            ],
            [
                'name' => 'Event Invitation',
                'category' => 'marketing',
                'body' => 'Dear {first_name}, you are invited to our upcoming event on {date}. Reply YES to confirm your attendance.',
                'variables' => ['first_name', 'date'],
                'status' => 'active',
            ],
            [
                'name' => 'Payment Reminder',
                'category' => 'reminder',
                'body' => 'Hi {first_name}, this is a friendly reminder that your payment of {amount} is due on {date}. Thank you.',
                'variables' => ['first_name', 'amount', 'date'],
                'status' => 'active',
            ],
            [
                'name' => 'General Notification',
                'category' => 'notification',
                'body' => 'Dear valued customer, {message}. Thank you for choosing Sendora.',
                'variables' => ['message'],
                'status' => 'active',
            ],
        ];

        foreach ($templates as $templateData) {
            SmsTemplate::create([
                ...$templateData,
                'created_by' => $owner->id,
            ]);
        }

        // Draft campaign
        SmsCampaign::create([
            'uuid' => Str::uuid(),
            'name' => 'Welcome Campaign',
            'message_body' => 'Hi there! Welcome to Sendora SMS Platform. We are excited to connect with you.',
            'sender_id' => 'SENDO',
            'target_type' => 'all_contacts',
            'target_filters' => null,
            'status' => 'draft',
            'created_by' => $owner->id,
        ]);

        // System settings
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
