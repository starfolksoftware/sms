<?php

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('complete authorization system enforces permissions correctly', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    // Create users with different roles
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    $marketingUser = User::factory()->create();
    $marketingUser->assignRole('marketing');

    $noRoleUser = User::factory()->create();

    // Create some test data
    $contact = Contact::factory()->create(['created_by' => $salesUser->id]);
    $deal = Deal::factory()->create(['created_by' => $salesUser->id, 'contact_id' => $contact->id]);
    $task = Task::factory()->create(['created_by' => $salesUser->id]);
    $product = Product::factory()->create(['created_by' => $admin->id]);

    // Test 1: Admin can access everything
    $this->actingAs($admin)->get('/contacts')->assertOk();
    $this->actingAs($admin)->get('/deals')->assertOk();
    $this->actingAs($admin)->get('/tasks')->assertOk();
    $this->actingAs($admin)->get('/products')->assertOk();
    $this->actingAs($admin)->get('/admin')->assertOk();

    // Test 2: Sales user can access CRM but not admin/products
    $this->actingAs($salesUser)->get('/contacts')->assertOk();
    $this->actingAs($salesUser)->get('/deals')->assertOk();
    $this->actingAs($salesUser)->get('/tasks')->assertOk();
    $this->actingAs($salesUser)->get('/sales')->assertOk();
    $this->actingAs($salesUser)->get('/admin')->assertForbidden();
    $this->actingAs($salesUser)->get('/products')->assertForbidden();

    // Test 3: Marketing user has limited access
    $this->actingAs($marketingUser)->get('/contacts')->assertForbidden();
    $this->actingAs($marketingUser)->get('/deals')->assertForbidden();
    $this->actingAs($marketingUser)->get('/tasks')->assertForbidden();
    $this->actingAs($marketingUser)->get('/products')->assertForbidden();
    $this->actingAs($marketingUser)->get('/admin')->assertForbidden();
    $this->actingAs($marketingUser)->get('/sales')->assertForbidden();
    $this->actingAs($marketingUser)->get('/marketing')->assertOk();

    // Test 4: User without roles cannot access protected resources
    $this->actingAs($noRoleUser)->get('/contacts')->assertForbidden();
    $this->actingAs($noRoleUser)->get('/deals')->assertForbidden();
    $this->actingAs($noRoleUser)->get('/tasks')->assertForbidden();
    $this->actingAs($noRoleUser)->get('/products')->assertForbidden();
    $this->actingAs($noRoleUser)->get('/admin')->assertForbidden();
    $this->actingAs($noRoleUser)->get('/sales')->assertForbidden();
    $this->actingAs($noRoleUser)->get('/marketing')->assertForbidden();

    // Test 5: Ownership-based authorization for modifications
    // Sales user can modify their own contact but not others
    $otherContact = Contact::factory()->create(['created_by' => $admin->id]);

    $this->actingAs($salesUser)->put("/contacts/{$contact->id}", [
        'name' => 'Updated Name',
        'email' => $contact->email,
        'phone' => $contact->phone,
        'company' => $contact->company,
        'notes' => $contact->notes,
    ])->assertOk();

    $this->actingAs($salesUser)->put("/contacts/{$otherContact->id}", [
        'name' => 'Updated Name',
        'email' => $otherContact->email,
        'phone' => $otherContact->phone,
        'company' => $otherContact->company,
        'notes' => $otherContact->notes,
    ])->assertForbidden();

    // Test 6: Admin can modify any resource regardless of ownership
    $this->actingAs($admin)->put("/contacts/{$contact->id}", [
        'name' => 'Admin Updated',
        'email' => $contact->email,
        'phone' => $contact->phone,
        'company' => $contact->company,
        'notes' => $contact->notes,
    ])->assertOk();

    // Test 7: Resource creation requires proper permissions
    $this->actingAs($salesUser)->post('/contacts', [
        'name' => 'New Contact',
        'email' => 'new@example.com',
        'phone' => '1234567890',
        'company' => 'Test Company',
    ])->assertCreated();

    $this->actingAs($marketingUser)->post('/contacts', [
        'name' => 'Marketing Contact',
        'email' => 'marketing@example.com',
    ])->assertForbidden();

    // Test 8: Unauthenticated access is properly blocked
    auth()->logout(); // Clear authentication
    $this->get('/contacts')->assertRedirect();
    $this->get('/admin')->assertRedirect();
    $this->post('/contacts', ['name' => 'Test'])->assertRedirect();

    expect(true)->toBeTrue(); // All assertions passed
});

test('middleware and policies work together for layered security', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');

    // Create contact owned by another user
    $otherUser = User::factory()->create();
    $contact = Contact::factory()->create(['created_by' => $otherUser->id]);

    // Test that both route middleware AND controller policies are enforced
    // Sales user has route access but policy denies modification of others' contacts
    $response = $this->actingAs($salesUser)->delete("/contacts/{$contact->id}");
    $response->assertForbidden();

    // Verify the contact still exists (wasn't deleted due to authorization failure)
    $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
});

test('error responses provide proper HTTP status codes', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $marketingUser = User::factory()->create();
    $marketingUser->assignRole('marketing');

    // Test various unauthorized operations return 403 Forbidden
    $this->actingAs($marketingUser)->get('/contacts')->assertStatus(403);
    $this->actingAs($marketingUser)->post('/contacts', ['name' => 'Test'])->assertStatus(403);
    $this->actingAs($marketingUser)->get('/admin')->assertStatus(403);

    // Test unauthenticated requests to dashboard redirect
    auth()->logout(); // Clear authentication
    $this->get('/dashboard')->assertRedirect();

    // Test unauthenticated API requests also redirect to login
    $this->get('/contacts')->assertRedirect();
});
