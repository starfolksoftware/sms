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
    $this->seed();
    $this->user = User::factory()->create();
    $this->user->assignRole('admin'); // Give admin permissions for testing
    $this->actingAs($this->user);
});

it('can list deals with filters and search', function () {
    $contact = Contact::factory()->create(['name' => 'John Doe']);
    $product = Product::factory()->create();

    Deal::factory(3)->create([
        'contact_id' => $contact->id,
        'product_id' => $product->id,
        'owner_id' => $this->user->id,
        'stage' => 'qualified',
        'status' => 'open',
    ]);

    Deal::factory(2)->create([
        'stage' => 'proposal',
        'status' => 'won',
    ]);

    $response = $this->getJson('/api/deals');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'count', 'per_page', 'current_page'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);

    // Test filtering by stage
    $response = $this->getJson('/api/deals?stage=qualified');
    $response->assertStatus(200);

    // Test search
    $response = $this->getJson('/api/deals?q=John');
    $response->assertStatus(200);
});

it('can create a new deal', function () {
    Event::fake();

    $contact = Contact::factory()->create();
    $product = Product::factory()->create();

    $dealData = [
        'title' => 'Test Deal',
        'description' => 'A test deal',
        'value' => 1000.00, // This is the required field from legacy
        'amount' => 1000.00,
        'currency' => 'USD',
        'contact_id' => $contact->id,
        'product_id' => $product->id,
        'expected_close_date' => now()->addDays(30)->format('Y-m-d'),
        'probability' => 75,
        'source' => 'website',
        'notes' => 'Test notes',
    ];

    $response = $this->postJson('/api/deals', $dealData);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'message' => 'Deal created successfully.',
        ]);

    $this->assertDatabaseHas('deals', [
        'title' => 'Test Deal',
        'amount' => 1000.00,
        'contact_id' => $contact->id,
    ]);

    Event::assertDispatched(DealCreated::class);
});

it('validates required fields when creating a deal', function () {
    $response = $this->postJson('/api/deals', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'value', 'currency', 'contact_id']);
});

it('can show a specific deal', function () {
    $contact = Contact::factory()->create();
    $deal = Deal::factory()->create(['contact_id' => $contact->id]);

    $response = $this->getJson("/api/deals/{$deal->id}");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'id' => $deal->id,
            'title' => $deal->title,
        ]);
});

it('can update a deal', function () {
    Event::fake();

    $contact = Contact::factory()->create();
    $deal = Deal::factory()->create([
        'contact_id' => $contact->id,
        'created_by' => $this->user->id,
        'status' => 'open', // Ensure it's open
    ]);

    $updateData = [
        'title' => 'Updated Deal',
        'value' => 2000.00,
        'amount' => 2000.00,
        'currency' => 'EUR',
        'contact_id' => $contact->id,
    ];

    $response = $this->putJson("/api/deals/{$deal->id}", $updateData);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'message' => 'Deal updated successfully.',
            'title' => 'Updated Deal',
        ]);

    Event::assertDispatched(DealUpdated::class);
});

it('cannot update a closed deal', function () {
    $contact = Contact::factory()->create();
    $deal = Deal::factory()->won()->create([
        'contact_id' => $contact->id,
        'created_by' => $this->user->id,
    ]);

    $updateData = [
        'title' => 'Updated Deal',
        'amount' => 2000.00,
        'currency' => 'USD',
        'contact_id' => $contact->id,
    ];

    $response = $this->putJson("/api/deals/{$deal->id}", $updateData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('can change deal stage', function () {
    Event::fake();

    $deal = Deal::factory()->create([
        'stage' => 'qualified',
        'created_by' => $this->user->id,
    ]);

    $response = $this->postJson("/api/deals/{$deal->id}/stage", [
        'stage' => 'proposal',
        'probability' => 80,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'message' => 'Deal stage changed successfully.',
        ]);

    $deal->refresh();
    expect($deal->stage)->toBe('proposal')
        ->and($deal->probability)->toBe(80);

    Event::assertDispatched(DealStageChanged::class);
});

it('can mark deal as won', function () {
    Event::fake();

    $deal = Deal::factory()->create([
        'amount' => 1000.00,
        'status' => 'open',
        'created_by' => $this->user->id,
    ]);

    $response = $this->postJson("/api/deals/{$deal->id}/win", [
        'won_amount' => 950.00,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'message' => 'Deal marked as won.',
        ]);

    $deal->refresh();
    expect($deal->status)->toBe('won')
        ->and($deal->won_amount)->toBe('950.00')
        ->and($deal->closed_at)->not->toBeNull();

    Event::assertDispatched(DealWon::class);
});

it('can mark deal as lost', function () {
    Event::fake();

    $deal = Deal::factory()->create([
        'status' => 'open',
        'created_by' => $this->user->id,
    ]);

    $response = $this->postJson("/api/deals/{$deal->id}/lose", [
        'lost_reason' => 'Budget constraints',
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'message' => 'Deal marked as lost.',
        ]);

    $deal->refresh();
    expect($deal->status)->toBe('lost')
        ->and($deal->lost_reason)->toBe('Budget constraints')
        ->and($deal->closed_at)->not->toBeNull();

    Event::assertDispatched(DealLost::class);
});

it('requires lost_reason when marking deal as lost', function () {
    $deal = Deal::factory()->create([
        'status' => 'open',
        'created_by' => $this->user->id,
    ]);

    $response = $this->postJson("/api/deals/{$deal->id}/lose", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['lost_reason']);
});

it('can delete a deal', function () {
    Event::fake();

    $deal = Deal::factory()->create(['created_by' => $this->user->id]);

    $response = $this->deleteJson("/api/deals/{$deal->id}");

    $response->assertStatus(204);

    $this->assertSoftDeleted('deals', ['id' => $deal->id]);

    Event::assertDispatched(DealDeleted::class);
});

it('can restore a deleted deal', function () {
    Event::fake();

    $deal = Deal::factory()->create(['created_by' => $this->user->id]);
    $deal->delete();

    $response = $this->postJson("/api/deals/{$deal->id}/restore");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'message' => 'Deal restored successfully.',
        ]);

    $deal->refresh();
    expect($deal->deleted_at)->toBeNull()
        ->and($deal->status)->toBe('open');

    Event::assertDispatched(DealRestored::class);
});

it('enforces authorization on deal operations', function () {
    $regularUser = User::factory()->create();
    $regularUser->assignRole('sales');
    $this->actingAs($regularUser);

    $otherUsersDeal = Deal::factory()->create([
        'created_by' => $this->user->id, // Different user
    ]);

    // Should not be able to update other user's deal
    $response = $this->putJson("/api/deals/{$otherUsersDeal->id}", [
        'title' => 'Updated Deal',
        'value' => 2000.00,
        'amount' => 2000.00,
        'currency' => 'USD',
        'contact_id' => Contact::factory()->create()->id,
    ]);

    $response->assertStatus(403);
});
