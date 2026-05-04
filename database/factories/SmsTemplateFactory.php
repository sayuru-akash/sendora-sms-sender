<?php

namespace Database\Factories;

use App\Models\SmsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsTemplate>
 */
class SmsTemplateFactory extends Factory
{
    protected $model = SmsTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['marketing', 'transactional', 'notification', 'reminder', null]),
            'body' => fake()->text(160),
            'variables' => null,
            'status' => 'active',
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'inactive']);
    }
}
