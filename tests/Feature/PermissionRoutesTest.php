<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin user can access admin routes', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertOk();
    $response->assertJson([
        'message' => 'Welcome to admin area',
        'user' => $admin->name,
    ]);
});

test('sales user can access sales routes but not admin routes', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $sales = User::factory()->create();
    $sales->assignRole('sales');

    // Should be able to access sales routes
    $response = $this->actingAs($sales)->get('/sales');
    $response->assertOk();

    // Should not be able to access admin routes
    $response = $this->actingAs($sales)->get('/admin');
    $response->assertForbidden();
});

test('marketing user can access marketing routes but not sales routes', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $marketing = User::factory()->create();
    $marketing->assignRole('marketing');

    // Should be able to access marketing routes
    $response = $this->actingAs($marketing)->get('/marketing');
    $response->assertOk();

    // Should not be able to access sales routes
    $response = $this->actingAs($marketing)->get('/sales');
    $response->assertForbidden();
});

test('user without roles cannot access protected routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
    $this->actingAs($user)->get('/sales')->assertForbidden();
    $this->actingAs($user)->get('/marketing')->assertForbidden();
});
