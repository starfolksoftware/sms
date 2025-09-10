<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('user can be assigned a role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'test_role']);

    $user->assignRole($role);

    expect($user->hasRole('test_role'))->toBeTrue();
});

test('user can be given a permission directly', function () {
    $user = User::factory()->create();
    $permission = Permission::create(['name' => 'test_permission']);

    $user->givePermissionTo($permission);

    expect($user->hasPermissionTo('test_permission'))->toBeTrue();
});

test('user inherits permissions from role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'manager']);
    $permission = Permission::create(['name' => 'manage_resources']);

    $role->givePermissionTo($permission);
    $user->assignRole($role);

    expect($user->hasPermissionTo('manage_resources'))->toBeTrue();
});

test('admin role has all required permissions', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $adminRole = Role::findByName('admin');

    $requiredPermissions = [
        'manage_clients',
        'manage_tasks',
        'view_dashboard',
        'manage_users',
        'manage_roles',
    ];

    foreach ($requiredPermissions as $permission) {
        expect($adminRole->hasPermissionTo($permission))->toBeTrue();
    }
});

test('sales role has correct permissions', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesRole = Role::findByName('sales');

    $allowedPermissions = [
        'manage_contacts',
        'manage_tasks',
        'view_dashboard',
        'view_reports',
        'view_analytics',
    ];

    $deniedPermissions = [
        'manage_users',
        'manage_roles',
        'manage_settings',
        'create_campaigns',
        'manage_campaigns',
        'manage_clients', // no longer assigned to sales
    ];

    foreach ($allowedPermissions as $permission) {
        expect($salesRole->hasPermissionTo($permission))->toBeTrue();
    }

    foreach ($deniedPermissions as $permission) {
        expect($salesRole->hasPermissionTo($permission))->toBeFalse();
    }
});

test('marketing role has correct permissions', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $marketingRole = Role::findByName('marketing');

    $allowedPermissions = [
        'view_dashboard',
        'create_campaigns',
        'manage_campaigns',
        'view_reports',
        'view_analytics',
    ];

    $deniedPermissions = [
        'manage_clients',
        'manage_tasks',
        'manage_users',
        'manage_roles',
        'manage_settings',
    ];

    foreach ($allowedPermissions as $permission) {
        expect($marketingRole->hasPermissionTo($permission))->toBeTrue();
    }

    foreach ($deniedPermissions as $permission) {
        expect($marketingRole->hasPermissionTo($permission))->toBeFalse();
    }
});

test('user with admin role can perform admin actions', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->can('manage_users'))->toBeTrue();
    expect($user->can('manage_roles'))->toBeTrue();
    expect($user->can('manage_settings'))->toBeTrue();
});

test('user with sales role can manage clients but not users', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('sales');

    // manage_clients removed; sales relies on manage_contacts instead
    expect($user->can('manage_contacts'))->toBeTrue();
    expect($user->can('manage_tasks'))->toBeTrue();
    expect($user->can('manage_users'))->toBeFalse();
    expect($user->can('manage_roles'))->toBeFalse();
});

test('user with marketing role can manage campaigns but not clients', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('marketing');

    expect($user->can('create_campaigns'))->toBeTrue();
    expect($user->can('manage_campaigns'))->toBeTrue();
    expect($user->can('manage_clients'))->toBeFalse();
    expect($user->can('manage_tasks'))->toBeFalse();
});
