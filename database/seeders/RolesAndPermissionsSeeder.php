<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Define permissions
        $permissions = [
            'manage_clients',
            'manage_tasks',
            'view_dashboard',
            'manage_roles',
            'view_audit_logs',
            'view_clients',
            'view_deals',
            'manage_deals',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles with their permissions
        $roles = [
            'Admin' => $permissions,
            'Sales' => ['manage_clients', 'view_dashboard', 'view_clients', 'view_deals', 'manage_deals'],
            'Marketing' => ['manage_clients', 'view_dashboard', 'view_clients', 'view_deals'],
            'Product' => ['manage_tasks', 'view_dashboard', 'view_deals'],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($perms);
        }

        // Assign Admin role to first user if exists
        $user = User::query()->orderBy('id')->first();
        if ($user && !$user->hasRole('Admin')) {
            $user->assignRole('Admin');
        }
    }
}
