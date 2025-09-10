<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $firstName . ' ' . $lastName,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'status' => fake()->randomElement(['lead', 'qualified', 'customer', 'archived']),
            'source' => fake()->randomElement(['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other']),
            'source_meta' => null,
            'owner_id' => null, // Will be set by tests or seeders
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
