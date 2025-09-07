<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productType = fake()->randomElement(['saas', 'info_product', 'digital']);

        return [
            'name' => fake()->words(2, true).' '.fake()->randomElement(['Pro', 'Plus', 'Basic', 'Premium']),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'product_type' => $productType,
            'stock_quantity' => $productType === 'physical' ? fake()->numberBetween(0, 100) : null,
            'active' => fake()->boolean(80), // 80% chance of being active
            'created_by' => User::factory(),
        ];
    }

    /**
     * Create a SaaS product
     */
    public function saas(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'saas',
            'name' => fake()->company().' '.fake()->randomElement(['Platform', 'Suite', 'Hub', 'Pro', 'Enterprise']),
            'description' => 'SaaS solution for '.fake()->bs(),
            'stock_quantity' => null,
        ]);
    }

    /**
     * Create an info product
     */
    public function infoProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'info_product',
            'name' => fake()->catchPhrase().' '.fake()->randomElement(['Course', 'Guide', 'Masterclass', 'Blueprint']),
            'description' => 'Learn how to '.fake()->bs(),
            'stock_quantity' => null,
        ]);
    }

    /**
     * Create a physical product
     */
    public function physical(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'physical',
            'stock_quantity' => fake()->numberBetween(0, 100),
        ]);
    }
}
