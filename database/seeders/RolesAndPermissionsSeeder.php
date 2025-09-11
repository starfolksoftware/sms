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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles with their permissions
        $roles = [
            'Admin' => $permissions,
            'Sales' => ['manage_clients', 'view_dashboard'],
            'Marketing' => ['manage_clients', 'view_dashboard'], // manage_clients for lead handling
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
