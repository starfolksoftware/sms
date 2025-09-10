<?php

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['view_contacts', 'create_contacts', 'edit_contacts', 'delete_contacts']);
    $this->actingAs($this->user);
});

test('contacts index page renders successfully', function () {
    $response = $this->get('/crm/contacts');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('crm/contacts/Index')
            ->has('contacts')
            ->has('filters')
            ->has('owners')
            ->has('statusOptions')
            ->has('sourceOptions')
    );
});

test('contacts index shows contact data', function () {
    $contact = Contact::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'status' => ContactStatus::Lead,
        'source' => ContactSource::Manual,
        'created_by' => $this->user->id,
        'owner_id' => $this->user->id,
    ]);

    $response = $this->get('/crm/contacts');

    $response->assertInertia(fn ($page) =>
        $page->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'John Doe')
            ->where('contacts.data.0.email', 'john@example.com')
    );
});

test('contacts create page renders successfully', function () {
    $response = $this->get('/crm/contacts/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('crm/contacts/Create')
            ->has('owners')
            ->has('statusOptions')
            ->has('sourceOptions')
    );
});

test('contact can be created via web form', function () {
    $contactData = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'phone' => '+1234567890',
        'company' => 'Acme Corp',
        'job_title' => 'Manager',
        'status' => ContactStatus::Lead->value,
        'source' => ContactSource::Manual->value,
        'owner_id' => $this->user->id,
        'notes' => 'Test contact',
    ];

    $response = $this->post('/crm/contacts', $contactData);

    $response->assertRedirect();
    
    $this->assertDatabaseHas('contacts', [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'phone' => '+1234567890',
        'company' => 'Acme Corp',
        'created_by' => $this->user->id,
    ]);
});

test('contact show page renders successfully', function () {
    $contact = Contact::factory()->create([
        'name' => 'John Doe',
        'created_by' => $this->user->id,
        'owner_id' => $this->user->id,
    ]);

    $response = $this->get("/crm/contacts/{$contact->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('crm/contacts/Show')
            ->has('contact')
            ->has('can')
            ->where('contact.name', 'John Doe')
    );
});

test('contact edit page renders successfully', function () {
    $contact = Contact::factory()->create([
        'name' => 'John Doe',
        'created_by' => $this->user->id,
        'owner_id' => $this->user->id,
    ]);

    $response = $this->get("/crm/contacts/{$contact->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('crm/contacts/Edit')
            ->has('contact')
            ->has('owners')
            ->has('statusOptions')
            ->has('sourceOptions')
    );
});

test('contact can be updated via web form', function () {
    $contact = Contact::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'created_by' => $this->user->id,
        'owner_id' => $this->user->id,
    ]);

    $updateData = [
        'first_name' => 'John',
        'last_name' => 'Smith', // Changed surname
        'email' => 'john.smith@example.com', // Changed email
        'phone' => '+1234567890',
        'company' => 'New Corp',
        'job_title' => 'Senior Manager',
        'status' => ContactStatus::Qualified->value,
        'source' => ContactSource::WebsiteForm->value,
        'owner_id' => $this->user->id,
        'notes' => 'Updated notes',
    ];

    $response = $this->put("/crm/contacts/{$contact->id}", $updateData);

    $response->assertRedirect();
    
    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'name' => 'John Smith',
        'email' => 'john.smith@example.com',
        'company' => 'New Corp',
        'status' => ContactStatus::Qualified->value,
    ]);
});

test('contact can be soft deleted via web', function () {
    $contact = Contact::factory()->create([
        'name' => 'John Doe',
        'created_by' => $this->user->id,
        'owner_id' => $this->user->id,
    ]);

    $response = $this->delete("/crm/contacts/{$contact->id}");

    $response->assertRedirect('/crm/contacts');
    
    $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
});

test('contact can be restored via web', function () {
    $contact = Contact::factory()->create([
        'name' => 'John Doe',
        'created_by' => $this->user->id,
        'owner_id' => $this->user->id,
    ]);
    
    $contact->delete(); // Soft delete
    
    $response = $this->post("/crm/contacts/{$contact->id}/restore");

    $response->assertRedirect();
    
    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'deleted_at' => null,
    ]);
});

test('unauthorized users cannot access contact pages', function () {
    // Create user without permissions
    $unauthorizedUser = User::factory()->create();
    $this->actingAs($unauthorizedUser);
    
    $response = $this->get('/crm/contacts');
    $response->assertStatus(403);
    
    $response = $this->get('/crm/contacts/create');
    $response->assertStatus(403);
});

test('contact list supports search functionality', function () {
    Contact::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'created_by' => $this->user->id,
    ]);
    
    Contact::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'created_by' => $this->user->id,
    ]);

    // Search by name
    $response = $this->get('/crm/contacts?search=John');
    $response->assertInertia(fn ($page) =>
        $page->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'John Doe')
    );
    
    // Search by email
    $response = $this->get('/crm/contacts?search=jane@example.com');
    $response->assertInertia(fn ($page) =>
        $page->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'Jane Smith')
    );
});

test('contact list supports status filtering', function () {
    Contact::factory()->create([
        'name' => 'Lead Contact',
        'status' => ContactStatus::Lead,
        'created_by' => $this->user->id,
    ]);
    
    Contact::factory()->create([
        'name' => 'Customer Contact',
        'status' => ContactStatus::Customer,
        'created_by' => $this->user->id,
    ]);

    $response = $this->get('/crm/contacts?status=' . ContactStatus::Lead->value);
    
    $response->assertInertia(fn ($page) =>
        $page->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'Lead Contact')
    );
});