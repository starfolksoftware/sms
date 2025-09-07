<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'value' => fake()->randomFloat(2, 100, 50000),
            'status' => fake()->randomElement(['open', 'won', 'lost']),
            'expected_close_date' => fake()->dateTimeBetween('now', '+6 months'),
            'contact_id' => Contact::factory(),
            'created_by' => User::factory(),
        ];
    }
}
