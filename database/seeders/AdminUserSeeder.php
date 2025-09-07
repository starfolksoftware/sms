<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $adminUser->assignRole('admin');

        // Create a sales user for testing
        $salesUser = User::create([
            'name' => 'Sales User',
            'email' => 'sales@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $salesUser->assignRole('sales');

        echo "Created admin user: admin@example.com / password\n";
        echo "Created sales user: sales@example.com / password\n";
    }
}
