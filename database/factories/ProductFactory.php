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
        return [
            'name' => fake()->words(2, true).' '.fake()->randomElement(['Pro', 'Plus', 'Basic', 'Premium']),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'active' => fake()->boolean(80), // 80% chance of being active
            'created_by' => User::factory(),
        ];
    }
}
