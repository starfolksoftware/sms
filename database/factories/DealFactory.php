<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Product;
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
            'description' => fake()->optional()->paragraph(),
            'contact_id' => Contact::factory(),
            'product_id' => fake()->optional()->randomElement([null, Product::factory()]),
            'owner_id' => fake()->optional()->randomElement([null, User::factory()]),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'stage' => fake()->randomElement(['new', 'qualified', 'proposal', 'negotiation', 'closed']),
            'status' => fake()->randomElement(['open', 'won', 'lost']),
            'expected_close_date' => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'probability' => fake()->optional()->numberBetween(0, 100),
            'lost_reason' => null, // Will be set conditionally
            'won_amount' => null, // Will be set conditionally
            'closed_at' => null, // Will be set conditionally
            'source' => fake()->randomElement(['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other']),
            'source_meta' => fake()->optional()->randomElements([
                'campaign' => fake()->word(),
                'medium' => fake()->word(),
                'term' => fake()->word(),
            ]),
            'notes' => fake()->optional()->text(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create a deal in 'open' status
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'stage' => fake()->randomElement(['new', 'qualified', 'proposal', 'negotiation']),
            'lost_reason' => null,
            'won_amount' => null,
            'closed_at' => null,
        ]);
    }

    /**
     * Create a deal in 'won' status
     */
    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'won',
            'stage' => 'closed',
            'won_amount' => $attributes['amount'] ?? fake()->randomFloat(2, 100, 50000),
            'closed_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'lost_reason' => null,
        ]);
    }

    /**
     * Create a deal in 'lost' status
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lost',
            'stage' => 'closed',
            'lost_reason' => fake()->sentence(),
            'closed_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'won_amount' => null,
        ]);
    }
}
