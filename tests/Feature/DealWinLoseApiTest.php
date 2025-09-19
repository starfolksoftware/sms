<?php

namespace Tests\Feature;

use App\Events\DealLost;
use App\Events\DealWon;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DealWinLoseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_user_can_mark_deal_as_won(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole('Sales');
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'open',
            'amount' => 1000.00,
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/win", [
                'won_amount' => 1200,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Deal marked as won successfully.',
            ])
            ->assertJsonPath('deal.status', 'won')
            ->assertJsonPath('deal.won_amount', 1200);

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'status' => 'won',
            'won_amount' => 1200,
        ]);

        Event::assertDispatched(DealWon::class);
    }

    public function test_won_amount_defaults_to_original_amount_when_not_provided(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole('Sales');
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'open',
            'amount' => 1000.00,
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/win");

        $response->assertStatus(200);

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'status' => 'won',
            'won_amount' => 1000.00,
        ]);
    }

    public function test_authorized_user_can_mark_deal_as_lost(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole('Sales');
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'open',
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/lose", [
                'lost_reason' => 'Customer chose a competitor',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Deal marked as lost successfully.',
            ])
            ->assertJsonPath('deal.status', 'lost')
            ->assertJsonPath('deal.lost_reason', 'Customer chose a competitor');

        $this->assertDatabaseHas('deals', [
            'id' => $deal->id,
            'status' => 'lost',
            'lost_reason' => 'Customer chose a competitor',
        ]);

        Event::assertDispatched(DealLost::class);
    }

    public function test_cannot_mark_closed_deal_as_won(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'won',
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/win");

        $response->assertStatus(403);
    }

    public function test_cannot_mark_closed_deal_as_lost(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'lost',
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/lose", [
                'lost_reason' => 'Some reason',
            ]);

        $response->assertStatus(403);
    }

    public function test_lost_reason_is_required_for_marking_lost(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'open',
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/lose");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lost_reason']);
    }

    public function test_lost_reason_must_be_at_least_5_characters(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'open',
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/lose", [
                'lost_reason' => 'No',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lost_reason']);
    }

    public function test_unauthorized_user_cannot_mark_deal_as_won(): void
    {
        $user = User::factory()->create();
        // Not assigning any role - no permissions
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'open',
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/win");

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_mark_deal_as_lost(): void
    {
        $user = User::factory()->create();
        // Not assigning any role - no permissions
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'open',
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/lose", [
                'lost_reason' => 'Some reason',
            ]);

        $response->assertStatus(403);
    }

    public function test_won_amount_validation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'status' => 'open',
            'contact_id' => $contact->id,
        ]);

        // Test negative amount
        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/win", [
                'won_amount' => -100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['won_amount']);

        // Test non-numeric amount
        $response = $this->actingAs($user)
            ->postJson("/api/deals/{$deal->id}/win", [
                'won_amount' => 'not-a-number',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['won_amount']);
    }
}
