<?php

namespace Database\Seeders;

use App\Models\DealStage;
use Illuminate\Database\Seeder;

class DealStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['name' => 'Lead In', 'slug' => 'lead-in', 'order' => 1, 'is_active' => true],
            ['name' => 'Qualified', 'slug' => 'qualified', 'order' => 2, 'is_active' => true],
            ['name' => 'Proposal Sent', 'slug' => 'proposal-sent', 'order' => 3, 'is_active' => true],
            ['name' => 'Negotiation', 'slug' => 'negotiation', 'order' => 4, 'is_active' => true],
            ['name' => 'Closed Won', 'slug' => 'closed-won', 'order' => 5, 'is_active' => true, 'is_winning_stage' => true],
            ['name' => 'Closed Lost', 'slug' => 'closed-lost', 'order' => 6, 'is_active' => true, 'is_losing_stage' => true],
        ];

        foreach ($stages as $stage) {
            DealStage::updateOrCreate(['slug' => $stage['slug']], $stage);
        }
    }
}
