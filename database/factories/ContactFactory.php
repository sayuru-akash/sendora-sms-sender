<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $phoneDigits = fake()->numerify('77#######');

        return [
            'uuid' => Str::uuid(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $firstName . ' ' . $lastName,
            'phone' => '0' . $phoneDigits,
            'phone_normalised' => '94' . $phoneDigits,
            'email' => fake()->optional(0.7)->safeEmail(),
            'company' => fake()->optional(0.5)->company(),
            'job_title' => fake()->optional(0.4)->jobTitle(),
            'country' => 'Sri Lanka',
            'district' => fake()->randomElement(['Colombo', 'Gampaha', 'Kandy', 'Galle', 'Matara', 'Jaffna', 'Kurunegala', 'Anuradhapura', 'Ratnapura', 'Badulla']),
            'city' => fake()->optional(0.6)->city(),
            'gender' => fake()->randomElement(['male', 'female', null]),
            'date_of_birth' => fake()->optional(0.3)->dateTimeBetween('-60 years', '-18 years'),
            'source' => fake()->randomElement(['manual', 'import', 'api', 'web_form', 'csv']),
            'status' => fake()->randomElement(['active', 'active', 'active', 'active', 'inactive', 'unsubscribed']),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'inactive']);
    }

    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'blocked',
            'blocked_at' => now(),
        ]);
    }

    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'invalid']);
    }
}
