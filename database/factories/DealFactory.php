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
        $status = fake()->randomElement(['open', 'won', 'lost']);
        $stage = $this->getStageForStatus($status);

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'value' => fake()->randomFloat(2, 100, 50000),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'status' => $status,
            'stage' => $stage,
            'expected_close_date' => fake()->dateTimeBetween('now', '+6 months'),
            'probability' => $this->getProbabilityForStage($stage),
            'lost_reason' => $status === 'lost' ? fake()->sentence() : null,
            'won_amount' => $status === 'won' ? fake()->randomFloat(2, 100, 50000) : null,
            'closed_at' => in_array($status, ['won', 'lost']) ? fake()->dateTimeBetween('-3 months', 'now') : null,
            'source' => fake()->randomElement(['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other']),
            'source_meta' => fake()->randomElement([
                null,
                ['utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'summer-sale'],
                ['referrer' => 'existing-customer', 'referrer_id' => fake()->numberBetween(1, 100)],
            ]),
            'notes' => fake()->optional()->paragraph(),
            'contact_id' => Contact::factory(),
            'product_id' => fake()->optional()->randomElement([null, Product::factory()]),
            'owner_id' => fake()->optional()->randomElement([null, User::factory()]),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Get appropriate stage for given status
     */
    private function getStageForStatus(string $status): string
    {
        return match ($status) {
            'won', 'lost' => 'closed',
            default => fake()->randomElement(['new', 'qualified', 'proposal', 'negotiation'])
        };
    }

    /**
     * Get probability based on stage
     */
    private function getProbabilityForStage(string $stage): int
    {
        return match ($stage) {
            'new' => fake()->numberBetween(10, 25),
            'qualified' => fake()->numberBetween(25, 50),
            'proposal' => fake()->numberBetween(50, 75),
            'negotiation' => fake()->numberBetween(75, 90),
            'closed' => 100,
            default => fake()->numberBetween(10, 90),
        };
    }

    /**
     * Create a deal that is won
     */
    public function won(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'won',
            'stage' => 'closed',
            'probability' => 100,
            'won_amount' => fake()->randomFloat(2, 100, 50000),
            'closed_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'lost_reason' => null,
        ]);
    }

    /**
     * Create a deal that is lost
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lost',
            'stage' => 'closed',
            'probability' => 0,
            'lost_reason' => fake()->sentence(),
            'closed_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'won_amount' => null,
        ]);
    }

    /**
     * Create an open deal
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'stage' => fake()->randomElement(['new', 'qualified', 'proposal', 'negotiation']),
            'closed_at' => null,
            'lost_reason' => null,
            'won_amount' => null,
        ]);
    }

    /**
     * Create a deal with high value
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => fake()->randomFloat(2, 25000, 100000),
            'amount' => fake()->randomFloat(2, 25000, 100000),
        ]);
    }
}
