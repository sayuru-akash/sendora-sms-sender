<?php

namespace Database\Factories;

use App\Models\SmsCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SmsCampaign>
 */
class SmsCampaignFactory extends Factory
{
    protected $model = SmsCampaign::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'name' => fake()->sentence(3),
            'message_body' => fake()->text(160),
            'sender_id' => 'SENDO',
            'target_type' => 'all_contacts',
            'target_filters' => null,
            'status' => 'draft',
            'scheduled_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'total_recipients' => 0,
            'queued_count' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
            'pending_count' => 0,
            'created_by' => null,
            'approved_by' => null,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => now()->addDay(),
        ]);
    }

    public function sending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sending',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }
}
