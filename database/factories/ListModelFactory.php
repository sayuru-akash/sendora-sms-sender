<?php

namespace Database\Factories;

use App\Models\ListModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListModel>
 */
class ListModelFactory extends Factory
{
    protected $model = ListModel::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => str_replace(' ', '-', strtolower($name)),
            'description' => fake()->optional(0.5)->sentence(),
            'colour' => fake()->hexColor(),
            'status' => 'active',
            'created_by' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'archived']);
    }
}
