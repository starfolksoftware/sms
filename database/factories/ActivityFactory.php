<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Activitylog\Models\Activity;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Spatie\Activitylog\Models\Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'log_name' => $this->faker->randomElement(['security', 'data_ops', 'default']),
            'description' => $this->faker->randomElement(['user_login', 'user_logout', 'data_exported', 'contact_created', 'contact_deleted']),
            'subject_type' => null,
            'subject_id' => null,
            'causer_type' => null,
            'causer_id' => null,
            'properties' => json_encode([
                'ip' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
            ]),
            'created_at' => $this->faker->dateTimeBetween('-1 year'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }
}
