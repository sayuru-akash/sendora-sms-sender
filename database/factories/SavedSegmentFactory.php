<?php

namespace Database\Factories;

use App\Models\SavedSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedSegment>
 */
class SavedSegmentFactory extends Factory
{
    protected $model = SavedSegment::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional(0.5)->sentence(),
            'filters' => [
                'status' => 'active',
            ],
            'created_by' => null,
        ];
    }
}
