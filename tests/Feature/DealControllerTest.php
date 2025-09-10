<?php

use App\Events\DealCreated;
use App\Events\DealDeleted;
use App\Events\DealLost;
use App\Events\DealRestored;
use App\Events\DealStageChanged;
use App\Events\DealUpdated;
use App\Events\DealWon;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    
    $this->salesUser = User::factory()->create();
    $this->salesUser->assignRole('sales');
    
    $this->contact = Contact::factory()->create(['created_by' => $this->salesUser->id]);
    $this->product = Product::factory()->create(['created_by' => $this->salesUser->id]);
});

test('authenticated user with manage_deals permission can list deals', function () {
    Deal::factory()->count(3)->create(['contact_id' => $this->contact->id, 'created_by' => $this->salesUser->id]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->get('/crm/deals');
    
    $response->assertOk();
    $response->assertJsonStructure(['deals']);
});

test('authenticated user can create a deal', function () {
    Event::fake();
    
    $dealData = [
        'title' => 'Test Deal',
        'description' => 'Test Description',
        'contact_id' => $this->contact->id,
        'product_id' => $this->product->id,
        'amount' => '1000.00',
        'currency' => 'USD',
        'stage' => 'new',
        'expected_close_date' => '2025-12-31',
        'probability' => 50,
        'source' => 'manual',
        'notes' => 'Test notes',
    ];
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post('/crm/deals', $dealData);
    
    $response->assertCreated();
    $response->assertJsonStructure(['deal']);
    
    $this->assertDatabaseHas('deals', [
        'title' => 'Test Deal',
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id,
        'status' => 'open',
    ]);
    
    Event::assertDispatched(DealCreated::class);
});

test('deal creation validates required fields', function () {
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post('/crm/deals', []);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title', 'contact_id', 'currency', 'stage', 'source']);
});

test('authenticated user can update a deal', function () {
    Event::fake();
    
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id
    ]);
    
    $updateData = [
        'title' => 'Updated Deal',
        'description' => 'Updated Description',
        'contact_id' => $this->contact->id,
        'amount' => '2000.00',
        'currency' => 'USD',
        'stage' => 'qualified',
        'source' => 'referral',
    ];
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->put("/crm/deals/{$deal->id}", $updateData);
    
    $response->assertOk();
    
    $this->assertDatabaseHas('deals', [
        'id' => $deal->id,
        'title' => 'Updated Deal',
        'stage' => 'qualified',
    ]);
    
    Event::assertDispatched(DealUpdated::class);
});

test('authenticated user can view a deal', function () {
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id
    ]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->get("/crm/deals/{$deal->id}");
    
    $response->assertOk();
    $response->assertJsonStructure(['deal']);
});

test('authenticated user can delete a deal', function () {
    Event::fake();
    
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id
    ]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->delete("/crm/deals/{$deal->id}");
    
    $response->assertOk();
    
    $this->assertSoftDeleted('deals', ['id' => $deal->id]);
    
    Event::assertDispatched(DealDeleted::class);
});

test('authenticated user can restore a deleted deal', function () {
    Event::fake();
    
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id
    ]);
    $deal->delete();
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/crm/deals/{$deal->id}/restore");
    
    $response->assertOk();
    
    $this->assertDatabaseHas('deals', [
        'id' => $deal->id,
        'deleted_at' => null,
    ]);
    
    Event::assertDispatched(DealRestored::class);
});

test('authenticated user can change deal stage', function () {
    Event::fake();
    
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id,
        'stage' => 'new',
        'status' => 'open'
    ]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/crm/deals/{$deal->id}/stage", ['stage' => 'qualified']);
    
    $response->assertOk();
    
    $this->assertDatabaseHas('deals', [
        'id' => $deal->id,
        'stage' => 'qualified',
    ]);
    
    Event::assertDispatched(DealStageChanged::class);
});

test('cannot change stage of closed deal', function () {
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id,
        'status' => 'won'
    ]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/crm/deals/{$deal->id}/stage", ['stage' => 'qualified']);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['stage']);
});

test('authenticated user can mark deal as won', function () {
    Event::fake();
    
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id,
        'amount' => '1000.00',
        'status' => 'open'
    ]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/crm/deals/{$deal->id}/win", ['won_amount' => '1200.00']);
    
    $response->assertOk();
    
    $deal->refresh();
    expect($deal->status)->toBe('won');
    expect($deal->won_amount)->toBe('1200.00');
    expect($deal->stage)->toBe('closed');
    expect($deal->closed_at)->not->toBeNull();
    
    Event::assertDispatched(DealWon::class);
});

test('authenticated user can mark deal as lost', function () {
    Event::fake();
    
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id,
        'status' => 'open'
    ]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/crm/deals/{$deal->id}/lose", ['lost_reason' => 'Price too high']);
    
    $response->assertOk();
    
    $deal->refresh();
    expect($deal->status)->toBe('lost');
    expect($deal->lost_reason)->toBe('Price too high');
    expect($deal->stage)->toBe('closed');
    expect($deal->closed_at)->not->toBeNull();
    
    Event::assertDispatched(DealLost::class);
});

test('marking deal as lost requires reason', function () {
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id,
        'status' => 'open'
    ]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/crm/deals/{$deal->id}/lose", []);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['lost_reason']);
});

test('cannot win or lose already closed deal', function () {
    $deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id,
        'status' => 'won'
    ]);
    
    $winResponse = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/crm/deals/{$deal->id}/win");
    
    $winResponse->assertStatus(422);
    
    $loseResponse = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->post("/crm/deals/{$deal->id}/lose", ['lost_reason' => 'test']);
    
    $loseResponse->assertStatus(422);
});

test('deals can be filtered by status', function () {
    Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id,
        'status' => 'open'
    ]);
    
    Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id,
        'status' => 'won'
    ]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->get('/crm/deals?status=won');
    
    $response->assertOk();
    
    $deals = $response->json('deals.data');
    expect(count($deals))->toBe(1);
    expect($deals[0]['status'])->toBe('won');
});

test('deals can be searched by title', function () {
    Deal::factory()->create([
        'title' => 'Important Deal',
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id
    ]);
    
    Deal::factory()->create([
        'title' => 'Regular Deal',
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id
    ]);
    
    $response = $this->actingAs($this->salesUser)
        ->withHeaders(['Accept' => 'application/json'])
        ->get('/crm/deals?q=Important');
    
    $response->assertOk();
    
    $deals = $response->json('deals.data');
    expect(count($deals))->toBe(1);
    expect($deals[0]['title'])->toBe('Important Deal');
});

test('unauthenticated users cannot access deals', function () {
    $response = $this->get('/crm/deals');
    
    $response->assertRedirect('/login');
});

test('users without manage_deals permission cannot create deals', function () {
    $user = User::factory()->create();
    // Not assigning any role, so no permissions
    
    $response = $this->actingAs($user)
        ->withHeaders(['Accept' => 'application/json'])
        ->post('/crm/deals', [
            'title' => 'Test Deal',
            'contact_id' => $this->contact->id,
            'currency' => 'USD',
            'stage' => 'new',
            'source' => 'manual',
        ]);
    
    $response->assertForbidden();
});