<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserNotificationPreference>
 */
class UserNotificationPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'event_type' => $this->faker->randomElement([
                'deal_created',
                'deal_stage_changed',
                'deal_won',
                'deal_lost',
                'deal_assigned',
            ]),
            'email_enabled' => $this->faker->boolean(),
            'database_enabled' => true, // Usually enabled by default
        ];
    }
}
