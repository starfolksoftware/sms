<?php

namespace Database\Factories;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
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
            'name' => "{$firstName} {$lastName}",
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'status' => fake()->randomElement(ContactStatus::cases())->value,
            'source' => fake()->randomElement(ContactSource::cases())->value,
            'source_meta' => fake()->boolean(30) ? [
                'campaign_id' => fake()->uuid(),
                'utm_source' => fake()->randomElement(['google', 'facebook', 'linkedin']),
                'utm_medium' => fake()->randomElement(['cpc', 'social', 'email']),
            ] : null,
            'notes' => fake()->optional(0.7)->sentence(),
            'owner_id' => fake()->boolean(70) ? User::factory() : null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the contact is a lead.
     */
    public function lead(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContactStatus::Lead->value,
        ]);
    }

    /**
     * Indicate that the contact is qualified.
     */
    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContactStatus::Qualified->value,
        ]);
    }

    /**
     * Indicate that the contact is a customer.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContactStatus::Customer->value,
        ]);
    }

    /**
     * Indicate that the contact is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContactStatus::Archived->value,
        ]);
    }

    /**
     * Indicate that the contact came from a website form.
     */
    public function fromWebsite(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => ContactSource::WebsiteForm->value,
            'source_meta' => [
                'form_id' => fake()->uuid(),
                'page_url' => fake()->url(),
                'utm_source' => 'website',
                'utm_medium' => 'organic',
            ],
        ]);
    }

    /**
     * Indicate that the contact came from social media.
     */
    public function fromSocial(): static
    {
        $source = fake()->randomElement([ContactSource::MetaAds, ContactSource::X, ContactSource::Instagram]);

        return $this->state(fn (array $attributes) => [
            'source' => $source->value,
            'source_meta' => [
                'campaign_id' => fake()->uuid(),
                'ad_id' => fake()->uuid(),
                'utm_source' => $source->value,
                'utm_medium' => 'social',
            ],
        ]);
    }
}
