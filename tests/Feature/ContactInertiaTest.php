<?php

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('contact index page renders correctly', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    // Create some test contacts
    Contact::factory()->count(3)->create();

    $response = $this->actingAs($salesUser)->get('/contacts');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/Index')
        ->has('contacts.data')
        ->has('users')
        ->has('canCreateContacts')
    );
});

test('contact create page renders correctly', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $response = $this->actingAs($salesUser)->get('/contacts/create');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/Create')
        ->has('users')
    );
});

test('contact show page renders correctly', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $contact = Contact::factory()->create(['created_by' => $salesUser->id]);

    $response = $this->actingAs($salesUser)->get("/contacts/{$contact->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/Show')
        ->has('contact')
        ->has('canEditContact')
        ->has('canDeleteContact')
    );
});

test('contact edit page renders correctly', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $contact = Contact::factory()->create(['created_by' => $salesUser->id]);

    $response = $this->actingAs($salesUser)->get("/contacts/{$contact->id}/edit");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/Edit')
        ->has('contact')
        ->has('users')
    );
});

test('contact filtering works correctly', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    // Create contacts with different statuses
    Contact::factory()->create(['status' => 'lead']);
    Contact::factory()->create(['status' => 'customer']);
    Contact::factory()->create(['status' => 'archived']);

    $response = $this->actingAs($salesUser)->get('/contacts?status=lead');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('contacts/Index')
        ->where('contacts.data', fn ($contacts) => count($contacts) === 1 && $contacts[0]['status'] === 'lead'
        )
    );
});
