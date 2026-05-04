<?php

namespace Database\Factories;

use App\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

    public function definition(): array
    {
        return [
            'filename' => fake()->uuid() . '.csv',
            'original_filename' => fake()->word() . '.csv',
            'file_path' => 'imports/' . fake()->uuid() . '.csv',
            'type' => 'csv',
            'status' => 'uploaded',
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'duplicate_rows' => 0,
            'invalid_rows' => 0,
            'column_mapping' => null,
            'options' => null,
            'list_id' => null,
            'created_by' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
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
