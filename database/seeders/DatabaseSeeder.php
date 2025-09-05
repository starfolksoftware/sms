<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Create an admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');

        // Create a sales user
        $sales = User::factory()->create([
            'name' => 'Sales User',
            'email' => 'sales@example.com',
        ]);
        $sales->assignRole('sales');

        // Create a marketing user
        $marketing = User::factory()->create([
            'name' => 'Marketing User',
            'email' => 'marketing@example.com',
        ]);
        $marketing->assignRole('marketing');

        // Keep the test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
