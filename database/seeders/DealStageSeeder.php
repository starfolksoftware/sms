<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DealStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample users, contacts, and products if they don't exist
        $user = User::factory()->create(['name' => 'Sales Manager']);
        $contact = Contact::factory()->create(['name' => 'Demo Contact']);
        $product = Product::factory()->create(['name' => 'Demo Product']);

        // Create deals with each of the default stages
        $stages = ['new', 'qualified', 'proposal', 'negotiation', 'closed'];
        $stageData = [
            'new' => ['probability' => 10, 'status' => 'open'],
            'qualified' => ['probability' => 25, 'status' => 'open'],
            'proposal' => ['probability' => 50, 'status' => 'open'],
            'negotiation' => ['probability' => 75, 'status' => 'open'],
            'closed' => ['probability' => 100, 'status' => 'won'],
        ];

        foreach ($stages as $stage) {
            Deal::factory()->create([
                'title' => "Sample {$stage} Deal",
                'stage' => $stage,
                'status' => $stageData[$stage]['status'],
                'probability' => $stageData[$stage]['probability'],
                'amount' => fake()->randomFloat(2, 1000, 10000),
                'currency' => 'USD',
                'source' => 'manual',
                'contact_id' => $contact->id,
                'product_id' => $product->id,
                'owner_id' => $user->id,
                'created_by' => $user->id,
            ]);
        }

        // Create a lost deal example
        Deal::factory()->lost()->create([
            'title' => 'Sample Lost Deal',
            'contact_id' => $contact->id,
            'owner_id' => $user->id,
            'created_by' => $user->id,
        ]);
    }
}
