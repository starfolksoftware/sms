<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->info('No users found. Please run UserSeeder first.');
            return;
        }

        $sources = ['website_form', 'meta_ads', 'x', 'instagram', 'referral', 'manual', 'other'];
        $statuses = ['lead', 'qualified', 'customer', 'archived'];

        // Create a variety of contacts with different statuses and sources
        foreach ($statuses as $status) {
            Contact::factory()->count(5)->create([
                'status' => $status,
                'source' => Arr::random($sources),
                'owner_id' => $users->random()->id,
                'created_by' => $users->random()->id,
                'source_meta' => $status === 'lead' ? [
                    'campaign_id' => fake()->uuid(),
                    'referrer_url' => fake()->url(),
                ] : null,
            ]);
        }

        // Create some additional contacts with specific characteristics
        
        // High-value customer contacts
        Contact::factory()->count(3)->create([
            'status' => 'customer',
            'source' => 'referral',
            'owner_id' => $users->random()->id,
            'created_by' => $users->random()->id,
            'company' => fake()->randomElement([
                'Google Inc.',
                'Microsoft Corporation', 
                'Apple Inc.',
                'Amazon.com Inc.',
                'Meta Platforms Inc.'
            ]),
            'job_title' => fake()->randomElement([
                'CEO',
                'CTO', 
                'VP of Engineering',
                'Director of Marketing',
                'Head of Sales'
            ]),
        ]);

        // Some archived contacts
        Contact::factory()->count(2)->create([
            'status' => 'archived',
            'source' => 'manual',
            'owner_id' => null,
            'created_by' => $users->random()->id,
            'notes' => 'Archived due to ' . fake()->randomElement([
                'no response after multiple attempts',
                'company went out of business',
                'not a good fit for our services',
                'budget constraints'
            ]),
        ]);

        $this->command->info('Contact seeder completed successfully.');
    }
}
