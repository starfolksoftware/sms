<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    
    // Create an admin user for testing
    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('admin');
    
    // Create a sales user for testing unauthorized access
    $this->salesUser = User::factory()->create();
    $this->salesUser->assignRole('sales');
});

test('admin can view roles management page', function () {
    $response = $this->actingAs($this->adminUser)->get('/admin/roles');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('admin/Roles')
            ->has('roles')
            ->has('permissions')
    );
});

test('non-admin cannot access roles management page', function () {
    $response = $this->actingAs($this->salesUser)->get('/admin/roles');

    $response->assertStatus(403);
});

test('admin can create a new role', function () {
    $permissions = ['view_contacts', 'create_contacts'];

    $response = $this->actingAs($this->adminUser)->post('/admin/roles', [
        'name' => 'test-role',
        'permissions' => $permissions,
    ]);

    $response->assertRedirect();
    
    $role = Role::where('name', 'test-role')->first();
    expect($role)->not->toBeNull();
    expect($role->permissions->pluck('name')->toArray())->toEqual($permissions);
});

test('admin can update an existing role', function () {
    $role = Role::create(['name' => 'test-role']);
    $role->givePermissionTo(['view_contacts']);

    $newPermissions = ['view_contacts', 'create_contacts', 'edit_contacts'];

    $response = $this->actingAs($this->adminUser)->put("/admin/roles/{$role->id}", [
        'name' => 'updated-role',
        'permissions' => $newPermissions,
    ]);

    $response->assertRedirect();
    
    $role->refresh();
    expect($role->name)->toBe('updated-role');
    expect($role->permissions->pluck('name')->toArray())->toEqual($newPermissions);
});

test('admin can delete a role', function () {
    $role = Role::create(['name' => 'deletable-role']);

    $response = $this->actingAs($this->adminUser)->delete("/admin/roles/{$role->id}");

    $response->assertRedirect();
    
    expect(Role::find($role->id))->toBeNull();
});

test('admin cannot delete the admin role', function () {
    $adminRole = Role::where('name', 'admin')->first();

    $response = $this->actingAs($this->adminUser)->delete("/admin/roles/{$adminRole->id}");

    $response->assertRedirect();
    $response->assertSessionHasErrors(['role']);
    
    expect(Role::find($adminRole->id))->not->toBeNull();
});

test('role creation requires unique name', function () {
    $response = $this->actingAs($this->adminUser)->post('/admin/roles', [
        'name' => 'admin', // This name already exists
        'permissions' => ['view_contacts'],
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('role creation validates permissions', function () {
    $response = $this->actingAs($this->adminUser)->post('/admin/roles', [
        'name' => 'test-role',
        'permissions' => ['invalid_permission'],
    ]);

    $response->assertSessionHasErrors(['permissions.0']);
});

test('non-admin cannot create roles', function () {
    $response = $this->actingAs($this->salesUser)->post('/admin/roles', [
        'name' => 'test-role',
        'permissions' => ['view_contacts'],
    ]);

    $response->assertStatus(403);
});

test('non-admin cannot update roles', function () {
    $role = Role::create(['name' => 'test-role']);

    $response = $this->actingAs($this->salesUser)->put("/admin/roles/{$role->id}", [
        'name' => 'updated-role',
        'permissions' => ['view_contacts'],
    ]);

    $response->assertStatus(403);
});

test('non-admin cannot delete roles', function () {
    $role = Role::create(['name' => 'test-role']);

    $response = $this->actingAs($this->salesUser)->delete("/admin/roles/{$role->id}");

    $response->assertStatus(403);
});
