<?php

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated sales user can list contacts', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $response = $this->actingAs($salesUser)->get('/api/contacts');

    $response->assertOk();
    $response->assertJsonStructure(['contacts', 'meta', 'links', 'message']);
});

test('authenticated sales user can create contact', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $contactData = [
        'name' => 'Test Contact',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'company' => 'Test Company',
        'notes' => 'Test notes',
    ];

    $response = $this->actingAs($salesUser)->post('/api/contacts', $contactData);

    $response->assertCreated();
    $response->assertJsonStructure(['contact', 'warnings', 'message']);

    $this->assertDatabaseHas('contacts', [
        'email' => 'test@example.com',
        'created_by' => $salesUser->id,
    ]);
});

test('sales user can update their own contact', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $contact = Contact::factory()->create(['created_by' => $salesUser->id]);

    $updateData = [
        'name' => 'Updated Name',
        'email' => $contact->email,
        'phone' => $contact->phone,
        'company' => $contact->company,
        'notes' => $contact->notes,
    ];

    $response = $this->actingAs($salesUser)->put("/api/contacts/{$contact->id}", $updateData);

    $response->assertOk();
    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'name' => 'Updated Name',
    ]);
});

test('sales user cannot update contact created by another user', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $otherUser = User::factory()->create();
    $contact = Contact::factory()->create(['created_by' => $otherUser->id]);

    $updateData = [
        'name' => 'Updated Name',
        'email' => $contact->email,
        'phone' => $contact->phone,
        'company' => $contact->company,
        'notes' => $contact->notes,
    ];

    $response = $this->actingAs($salesUser)->put("/api/contacts/{$contact->id}", $updateData);

    $response->assertForbidden();
});

test('sales user can delete their own contact', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $contact = Contact::factory()->create(['created_by' => $salesUser->id]);

    $response = $this->actingAs($salesUser)->delete("/api/contacts/{$contact->id}");

    $response->assertOk();
    $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
});

test('sales user cannot delete contact created by another user', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $otherUser = User::factory()->create();
    $contact = Contact::factory()->create(['created_by' => $otherUser->id]);

    $response = $this->actingAs($salesUser)->delete("/api/contacts/{$contact->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
});

test('marketing user cannot access contacts', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $marketingUser = User::factory()->create();
    $marketingUser->assignRole('marketing');

    $response = $this->actingAs($marketingUser)->get('/api/contacts');
    $response->assertForbidden();

    $response = $this->actingAs($marketingUser)->post('/api/contacts', [
        'name' => 'Test Contact',
        'email' => 'test@example.com',
    ]);
    $response->assertForbidden();
});

test('admin can access and modify any contact', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $otherUser = User::factory()->create();
    $contact = Contact::factory()->create(['created_by' => $otherUser->id]);

    // Admin can view contacts
    $response = $this->actingAs($admin)->get('/api/contacts');
    $response->assertOk();

    // Admin can update any contact
    $updateData = [
        'name' => 'Admin Updated',
        'email' => $contact->email,
        'phone' => $contact->phone,
        'company' => $contact->company,
        'notes' => $contact->notes,
    ];

    $response = $this->actingAs($admin)->put("/api/contacts/{$contact->id}", $updateData);
    $response->assertOk();

    // Admin can delete any contact
    $response = $this->actingAs($admin)->delete("/api/contacts/{$contact->id}");
    $response->assertOk();
});

test('unauthenticated user cannot access contacts', function () {
    $response = $this->get('/api/contacts');
    $response->assertRedirect(); // Should redirect to login

    $response = $this->post('/api/contacts', [
        'name' => 'Test Contact',
        'email' => 'test@example.com',
    ]);
    $response->assertRedirect(); // Should redirect to login
});
