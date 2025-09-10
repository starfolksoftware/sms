<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some users to be owners and creators
        $salesUsers = User::factory()->count(3)->create();

        // Assign sales role to users
        foreach ($salesUsers as $user) {
            $user->assignRole('sales');
        }

        // Create diverse contacts with different statuses and sources
        Contact::factory()
            ->count(5)
            ->lead()
            ->fromWebsite()
            ->create([
                'created_by' => $salesUsers->random()->id,
                'owner_id' => $salesUsers->random()->id,
            ]);

        Contact::factory()
            ->count(8)
            ->lead()
            ->fromSocial()
            ->create([
                'created_by' => $salesUsers->random()->id,
                'owner_id' => $salesUsers->random()->id,
            ]);

        Contact::factory()
            ->count(3)
            ->qualified()
            ->create([
                'created_by' => $salesUsers->random()->id,
                'owner_id' => $salesUsers->random()->id,
            ]);

        Contact::factory()
            ->count(4)
            ->customer()
            ->create([
                'created_by' => $salesUsers->random()->id,
                'owner_id' => $salesUsers->random()->id,
            ]);

        Contact::factory()
            ->count(2)
            ->archived()
            ->create([
                'created_by' => $salesUsers->random()->id,
                'owner_id' => null,
            ]);
    }
}
