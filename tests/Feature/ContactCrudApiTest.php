<?php

use App\Enums\ContactStatus;
use App\Events\ContactCreated;
use App\Events\ContactDeleted;
use App\Events\ContactRestored;
use App\Events\ContactUpdated;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('contacts can be listed with pagination', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Contact::factory()->count(20)->create();

    $response = $this->actingAs($user)->get('/api/contacts');

    $response->assertOk();
    $response->assertJsonStructure([
        'contacts',
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        'links' => ['first', 'last', 'prev', 'next'],
        'message',
    ]);

    expect($response->json('contacts'))->toHaveCount(15); // Default per_page
    expect($response->json('meta.total'))->toBe(20);
});

test('contacts can be filtered by status', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Contact::factory()->create(['status' => ContactStatus::Lead]);
    Contact::factory()->create(['status' => ContactStatus::Customer]);

    $response = $this->actingAs($user)->get('/api/contacts?status=lead');

    $response->assertOk();
    expect($response->json('contacts'))->toHaveCount(1);
    expect($response->json('contacts.0.status'))->toBe('lead');
});

test('contacts can be searched', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Contact::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    Contact::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $response = $this->actingAs($user)->get('/api/contacts?search=John');

    $response->assertOk();
    expect($response->json('contacts'))->toHaveCount(1);
    expect($response->json('contacts.0.name'))->toBe('John Doe');
});

test('contacts can be sorted', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $contact1 = Contact::factory()->create(['name' => 'Alice']);
    $contact2 = Contact::factory()->create(['name' => 'Bob']);

    $response = $this->actingAs($user)->get('/api/contacts?sort_by=name&sort_direction=asc');

    $response->assertOk();
    expect($response->json('contacts.0.name'))->toBe('Alice');
    expect($response->json('contacts.1.name'))->toBe('Bob');
});

test('duplicate email detection prevents creation', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Contact::factory()->create(['email' => 'duplicate@example.com', 'name' => 'Existing Contact']);

    $response = $this->actingAs($user)->post('/api/contacts', [
        'name' => 'New Contact',
        'email' => 'duplicate@example.com',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonPath('errors.email.0', function ($message) {
        return str_contains($message, 'already exists') && str_contains($message, 'Existing Contact');
    });
});

test('phone duplicate detection shows warning', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Contact::factory()->create(['phone' => '555-123-4567', 'name' => 'Existing Contact']);

    $response = $this->actingAs($user)->post('/api/contacts', [
        'name' => 'New Contact',
        'email' => 'new@example.com',
        'phone' => '555-123-4567',
    ]);

    $response->assertCreated();
    expect($response->json('warnings'))->toHaveKey('phone');
    expect($response->json('warnings.phone'))->toContain('similar phone number already exists');
});

test('contact creation fires event', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->post('/api/contacts', [
        'name' => 'Test Contact',
        'email' => 'test@example.com',
    ]);

    $response->assertCreated();
    Event::assertDispatched(ContactCreated::class);
});

test('contact update fires event', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $contact = Contact::factory()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)->put("/api/contacts/{$contact->id}", [
        'name' => 'Updated Contact',
    ]);

    $response->assertOk();
    Event::assertDispatched(ContactUpdated::class);
});

test('contact deletion fires event', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $contact = Contact::factory()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)->delete("/api/contacts/{$contact->id}");

    $response->assertOk();
    Event::assertDispatched(ContactDeleted::class);

    expect($contact->fresh()->trashed())->toBeTrue();
});

test('contact can be restored', function () {
    Event::fake();

    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $contact = Contact::factory()->create(['created_by' => $user->id]);
    $contact->delete();

    $response = $this->actingAs($user)->post("/api/contacts/{$contact->id}/restore");

    $response->assertOk();
    Event::assertDispatched(ContactRestored::class);

    expect($contact->fresh()->trashed())->toBeFalse();
});

test('contact restore validates uniqueness constraints', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $contact = Contact::factory()->create(['email' => 'test@example.com', 'created_by' => $user->id]);
    $contact->delete();

    // Create another contact with the same email
    Contact::factory()->create(['email' => 'test@example.com']);

    $response = $this->actingAs($user)->post("/api/contacts/{$contact->id}/restore");

    $response->assertUnprocessable();
    $response->assertJsonPath('errors.email.0', 'Cannot restore: A contact with this email already exists.');
});

test('only admin users can restore contacts', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $contact = Contact::factory()->create(['created_by' => $salesUser->id]);
    $contact->delete();

    $response = $this->actingAs($salesUser)->post("/api/contacts/{$contact->id}/restore");

    $response->assertForbidden();
});
