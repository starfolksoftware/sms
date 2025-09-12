<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Deal> */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        $stages = ['new','qualified','proposal','negotiation','closed'];
        $status = 'open';
        $stage = $this->faker->randomElement($stages);
        $amount = $this->faker->optional(0.7)->randomFloat(2, 100, 10000);
        return [
            'title' => $this->faker->sentence(3),
            'contact_id' => Contact::query()->inRandomOrder()->value('id') ?? Contact::factory(),
            'product_id' => null,
            'owner_id' => User::query()->inRandomOrder()->value('id'),
            'amount' => $amount,
            'currency' => 'USD',
            'stage' => $stage,
            'status' => $status,
            'expected_close_date' => $this->faker->optional()->dateTimeBetween('now','+2 months'),
            'probability' => $this->faker->optional()->numberBetween(10,90),
            'source' => 'manual',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
