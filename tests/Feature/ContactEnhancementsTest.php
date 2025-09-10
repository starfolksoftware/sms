<?php

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('contact can be created with status and source', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $owner = User::factory()->create();
    $owner->assignRole('sales');

    $contactData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
        'company' => 'Test Company',
        'job_title' => 'Manager',
        'status' => ContactStatus::Qualified->value,
        'source' => ContactSource::WebsiteForm->value,
        'source_meta' => [
            'campaign_id' => '123',
            'utm_source' => 'google',
        ],
        'notes' => 'Test notes',
        'owner_id' => $owner->id,
    ];

    $response = $this->actingAs($salesUser)->post('/contacts', $contactData);

    $response->assertCreated();
    $response->assertJsonStructure(['contact', 'message']);

    $this->assertDatabaseHas('contacts', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'status' => ContactStatus::Qualified->value,
        'source' => ContactSource::WebsiteForm->value,
        'owner_id' => $owner->id,
        'created_by' => $salesUser->id,
    ]);
});

test('contact status defaults to lead when not specified', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $contactData = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
    ];

    $response = $this->actingAs($salesUser)->post('/contacts', $contactData);

    $response->assertCreated();

    $this->assertDatabaseHas('contacts', [
        'email' => 'jane@example.com',
        'status' => ContactStatus::Lead->value,
        'source' => ContactSource::Manual->value,
    ]);
});

test('contact status can be updated', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $contact = Contact::factory()->create([
        'status' => ContactStatus::Lead,
        'created_by' => $salesUser->id,
    ]);

    $updateData = [
        'first_name' => $contact->first_name,
        'last_name' => $contact->last_name,
        'email' => $contact->email,
        'status' => ContactStatus::Customer->value,
    ];

    $response = $this->actingAs($salesUser)->put("/contacts/{$contact->id}", $updateData);

    $response->assertOk();

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'status' => ContactStatus::Customer->value,
    ]);
});

test('contact email must be unique ignoring soft deleted contacts', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    // Create and soft delete a contact
    $deletedContact = Contact::factory()->create([
        'email' => 'test@example.com',
        'created_by' => $salesUser->id,
    ]);
    $deletedContact->delete();

    // Should be able to create a new contact with same email
    $contactData = [
        'first_name' => 'New',
        'last_name' => 'Contact',
        'email' => 'test@example.com',
    ];

    $response = $this->actingAs($salesUser)->post('/contacts', $contactData);

    $response->assertCreated();

    $this->assertDatabaseHas('contacts', [
        'email' => 'test@example.com',
        'deleted_at' => null,
    ]);
});

test('contact can be assigned to an owner', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $owner = User::factory()->create();
    $owner->assignRole('sales');

    $contact = Contact::factory()->create([
        'created_by' => $salesUser->id,
        'owner_id' => null,
    ]);

    $updateData = [
        'first_name' => $contact->first_name,
        'last_name' => $contact->last_name,
        'email' => $contact->email,
        'owner_id' => $owner->id,
    ];

    $response = $this->actingAs($salesUser)->put("/contacts/{$contact->id}", $updateData);

    $response->assertOk();

    $contact->refresh();
    expect($contact->owner_id)->toBe($owner->id);
    expect($contact->owner)->toBeInstanceOf(User::class);
    expect($contact->owner->id)->toBe($owner->id);
});

test('contact full name accessor works correctly', function () {
    $contact = Contact::factory()->make([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'name' => null,
    ]);

    expect($contact->full_name)->toBe('John Doe');

    $contact2 = Contact::factory()->make([
        'first_name' => null,
        'last_name' => null,
        'name' => 'Jane Smith',
    ]);

    expect($contact2->full_name)->toBe('Jane Smith');
});

test('contact status and source enums work correctly', function () {
    $contact = Contact::factory()->create([
        'status' => ContactStatus::Qualified,
        'source' => ContactSource::MetaAds,
    ]);

    expect($contact->status)->toBeInstanceOf(ContactStatus::class);
    expect($contact->status)->toBe(ContactStatus::Qualified);
    expect($contact->status->displayName())->toBe('Qualified');

    expect($contact->source)->toBeInstanceOf(ContactSource::class);
    expect($contact->source)->toBe(ContactSource::MetaAds);
    expect($contact->source->displayName())->toBe('Meta Ads');
});

test('contact relationships work correctly', function () {
    $creator = User::factory()->create();
    $owner = User::factory()->create();

    $contact = Contact::factory()->create([
        'created_by' => $creator->id,
        'owner_id' => $owner->id,
    ]);

    expect($contact->creator)->toBeInstanceOf(User::class);
    expect($contact->creator->id)->toBe($creator->id);

    expect($contact->owner)->toBeInstanceOf(User::class);
    expect($contact->owner->id)->toBe($owner->id);

    // Test tasks relationship
    expect($contact->tasks())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);

    // Test deals relationship
    expect($contact->deals())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});
