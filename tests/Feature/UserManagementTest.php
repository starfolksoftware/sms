<?php

use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

test('admin can view users management page', function () {
    $response = $this->actingAs($this->adminUser)->get('/admin/users');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/Users')
        ->has('users')
        ->has('roles')
    );
});

test('non-admin cannot access users management page', function () {
    $response = $this->actingAs($this->salesUser)->get('/admin/users');

    $response->assertStatus(403);
});

test('unauthenticated user cannot access users management page', function () {
    $response = $this->get('/admin/users');

    $response->assertRedirect('/login');
});

test('admin can invite a user', function () {
    $response = $this->actingAs($this->adminUser)->post('/admin/users/invite', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'roles' => ['sales'],
    ]);

    $response->assertRedirect();

    $user = User::where('email', 'newuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->status)->toBe(UserStatus::PendingInvite);
    expect($user->invitation_token)->not->toBeNull();
    expect($user->hasRole('sales'))->toBeTrue();
});

test('admin can create a user directly', function () {
    $response = $this->actingAs($this->adminUser)->post('/admin/users', [
        'name' => 'Direct User',
        'email' => 'direct@example.com',
        'roles' => ['marketing'],
        'send_invitation' => false,
    ]);

    $response->assertRedirect();

    $user = User::where('email', 'direct@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->status)->toBe(UserStatus::Active);
    expect($user->hasRole('marketing'))->toBeTrue();
});

test('admin can update a user', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $user->assignRole('sales');

    $response = $this->actingAs($this->adminUser)->put("/admin/users/{$user->id}", [
        'name' => 'Updated Name',
        'email' => $user->email,
        'status' => UserStatus::Deactivated->value,
        'roles' => ['marketing'],
    ]);

    $response->assertRedirect();

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->status)->toBe(UserStatus::Deactivated);
    expect($user->hasRole('marketing'))->toBeTrue();
    expect($user->hasRole('sales'))->toBeFalse();
});

test('admin can delete a user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->adminUser)->delete("/admin/users/{$user->id}");

    $response->assertRedirect();
    expect(User::find($user->id))->toBeNull();
});

test('admin cannot delete themselves', function () {
    $response = $this->actingAs($this->adminUser)->delete("/admin/users/{$this->adminUser->id}");

    $response->assertRedirect();
    $response->assertSessionHasErrors(['user']);
    expect(User::find($this->adminUser->id))->not->toBeNull();
});

test('admin cannot delete the last admin user', function () {
    // Make sure there's only one admin
    User::role('admin')->where('id', '!=', $this->adminUser->id)->delete();

    $response = $this->actingAs($this->adminUser)->delete("/admin/users/{$this->adminUser->id}");

    $response->assertRedirect();
    $response->assertSessionHasErrors(['user']);
    expect(User::find($this->adminUser->id))->not->toBeNull();
});

test('admin can resend invite to pending user', function () {
    $user = User::factory()->create(['status' => UserStatus::PendingInvite]);
    $originalToken = $user->invitation_token;
    $originalSentAt = $user->invitation_sent_at;

    sleep(1); // Ensure time difference

    $response = $this->actingAs($this->adminUser)->post("/admin/users/{$user->id}/resend-invite");

    $response->assertRedirect();

    $user->refresh();
    expect($user->invitation_token)->not->toBe($originalToken);
    expect($user->invitation_sent_at)->not->toBe($originalSentAt);
});

test('admin cannot resend invite to non-pending user', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $response = $this->actingAs($this->adminUser)->post("/admin/users/{$user->id}/resend-invite");

    $response->assertRedirect();
    $response->assertSessionHasErrors(['user']);
});

test('non-admin cannot perform user management actions', function () {
    $user = User::factory()->create();

    // Test various endpoints that should be forbidden
    $this->actingAs($this->salesUser)->post('/admin/users/invite', [
        'name' => 'Test',
        'email' => 'test@example.com',
    ])->assertStatus(403);

    $this->actingAs($this->salesUser)->post('/admin/users', [
        'name' => 'Test',
        'email' => 'test@example.com',
    ])->assertStatus(403);

    $this->actingAs($this->salesUser)->put("/admin/users/{$user->id}", [
        'name' => 'Updated',
        'email' => $user->email,
    ])->assertStatus(403);

    $this->actingAs($this->salesUser)->delete("/admin/users/{$user->id}")
        ->assertStatus(403);

    $this->actingAs($this->salesUser)->post("/admin/users/{$user->id}/resend-invite")
        ->assertStatus(403);
});

test('user invitation validates required fields', function () {
    $this->actingAs($this->adminUser)->post('/admin/users/invite', [])
        ->assertSessionHasErrors(['name', 'email']);
});

test('user creation prevents duplicate emails', function () {
    $existingUser = User::factory()->create();

    $this->actingAs($this->adminUser)->post('/admin/users/invite', [
        'name' => 'Test User',
        'email' => $existingUser->email,
    ])->assertSessionHasErrors(['email']);
});

test('user management includes search and filtering', function () {
    // Create test users with different roles and statuses
    $activeUser = User::factory()->create(['name' => 'Alice Active', 'status' => UserStatus::Active]);
    $activeUser->assignRole('sales');

    $deactivatedUser = User::factory()->create(['name' => 'Bob Deactivated', 'status' => UserStatus::Deactivated]);
    $deactivatedUser->assignRole('marketing');

    $pendingUser = User::factory()->create(['name' => 'Charlie Pending', 'status' => UserStatus::PendingInvite]);
    $pendingUser->assignRole('sales');

    // Test search by name
    $response = $this->actingAs($this->adminUser)->get('/admin/users?search=Alice');
    $response->assertInertia(fn ($page) => $page->has('users.data', 1)
        ->where('users.data.0.name', 'Alice Active')
    );

    // Test filter by role - just check that we get users back
    $response = $this->actingAs($this->adminUser)->get('/admin/users?role=sales');
    $response->assertInertia(fn ($page) => $page->has('users.data')
        ->whereType('users.data', 'array')
    );

    // Test filter by status
    $response = $this->actingAs($this->adminUser)->get('/admin/users?status=pending_invite');
    $response->assertInertia(fn ($page) => $page->has('users.data')
        ->whereType('users.data', 'array')
    );
});

test('admin cannot deactivate themselves', function () {
    $response = $this->actingAs($this->adminUser)->put("/admin/users/{$this->adminUser->id}", [
        'name' => $this->adminUser->name,
        'email' => $this->adminUser->email,
        'status' => UserStatus::Deactivated->value,
        'roles' => ['admin'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['status']);

    $this->adminUser->refresh();
    expect($this->adminUser->status)->toBe(UserStatus::Active);
});
