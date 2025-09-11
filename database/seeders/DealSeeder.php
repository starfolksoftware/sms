<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DealSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some test deals if we have users and contacts
        $users = User::take(3)->get();
        $contacts = Contact::take(5)->get();
        $products = Product::take(3)->get();
        
        if ($users->isNotEmpty() && $contacts->isNotEmpty()) {
            Deal::factory()->count(10)->create([
                'contact_id' => $contacts->random()->id,
                'product_id' => $products->isNotEmpty() ? $products->random()->id : null,
                'owner_id' => $users->random()->id,
                'created_by' => $users->random()->id,
            ]);
            
            // Create some specific deals in different states
            Deal::factory()->open()->create([
                'title' => 'Big Software Deal',
                'contact_id' => $contacts->first()->id,
                'owner_id' => $users->first()->id,
                'created_by' => $users->first()->id,
                'amount' => 25000,
            ]);
            
            Deal::factory()->won()->create([
                'title' => 'Closed Won Deal',
                'contact_id' => $contacts->first()->id,
                'owner_id' => $users->first()->id,
                'created_by' => $users->first()->id,
                'amount' => 15000,
            ]);
            
            Deal::factory()->lost()->create([
                'title' => 'Lost Opportunity',
                'contact_id' => $contacts->last()->id,
                'owner_id' => $users->last()->id,
                'created_by' => $users->last()->id,
                'amount' => 5000,
            ]);
        }
    }
}