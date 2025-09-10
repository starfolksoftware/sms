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
            'manage_contacts',
            'view_contacts',
            'create_contacts',
            'edit_contacts',
            'delete_contacts',
            'manage_deals',
            'view_deals',
            'create_deals',
            'edit_deals',
            'delete_deals',
            'manage_tasks',
            'view_tasks',
            'create_tasks',
            'edit_tasks',
            'delete_tasks',
            'manage_products',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_dashboard',
            'manage_users',
            'manage_roles',
            'manage_settings',
            'view_reports',
            'create_campaigns',
            'manage_campaigns',
            'view_analytics',
            'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $salesRole = Role::firstOrCreate(['name' => 'sales']);
        $marketingRole = Role::firstOrCreate(['name' => 'marketing']);

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Sales role permissions (no broad manage_clients so ownership rules apply)
        $salesRole->givePermissionTo([
            'manage_contacts',
            'view_contacts',
            'create_contacts',
            'edit_contacts',
            'delete_contacts',
            'manage_deals',
            'view_deals',
            'create_deals',
            'edit_deals',
            'delete_deals',
            'manage_tasks',
            'view_tasks',
            'create_tasks',
            'edit_tasks',
            'delete_tasks',
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
