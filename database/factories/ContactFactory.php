<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        $first = $this->faker->firstName();
        $last = $this->faker->lastName();
        $email = strtolower($first.'.'.$last.'@example.test');
        return [
            'first_name' => $first,
            'last_name' => $last,
            'name' => "$first $last",
            'email' => $email,
            'phone' => $this->faker->e164PhoneNumber(),
            'company' => $this->faker->company(),
            'job_title' => $this->faker->jobTitle(),
            'status' => 'lead',
            'source' => 'manual',
            'source_meta' => null,
            'notes' => $this->faker->sentence(),
        ];
    }
}
