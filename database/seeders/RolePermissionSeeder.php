<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage_clients',
            'manage_tasks',
            'view_dashboard',
            'manage_users',
            'manage_roles',
            'manage_settings',
            'view_reports',
            'create_campaigns',
            'manage_campaigns',
            'view_analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $salesRole = Role::create(['name' => 'sales']);
        $marketingRole = Role::create(['name' => 'marketing']);

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Sales role permissions
        $salesRole->givePermissionTo([
            'manage_clients',
            'manage_tasks',
            'view_dashboard',
            'view_reports',
            'view_analytics',
        ]);

        // Marketing role permissions
        $marketingRole->givePermissionTo([
            'view_dashboard',
            'create_campaigns',
            'manage_campaigns',
            'view_reports',
            'view_analytics',
        ]);
    }
}
