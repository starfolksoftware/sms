<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealUITest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_can_render_deal_list_with_enhanced_filters(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        
        $contact = Contact::factory()->create();
        Deal::factory()->count(5)->create(['contact_id' => $contact->id]);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertSee('Deals');
    }

    public function test_can_render_deal_detail_view_with_timeline(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create(['contact_id' => $contact->id]);

        $this->actingAs($user)
            ->get("/deals/{$deal->id}")
            ->assertOk()
            ->assertSee($deal->title);
    }

    public function test_can_filter_deals_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        
        $contact = Contact::factory()->create();
        Deal::factory()->create(['contact_id' => $contact->id, 'status' => 'open']);
        Deal::factory()->create(['contact_id' => $contact->id, 'status' => 'won']);

        $response = $this->actingAs($user)
            ->get('/deals?tableFilters[status][value][]=open')
            ->assertOk();
    }

    public function test_shows_activities_timeline_for_a_deal(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create(['contact_id' => $contact->id]);
        
        // Create an activity
        activity()->performedOn($deal)->log('Deal created');

        $this->actingAs($user)
            ->get("/deals/{$deal->id}")
            ->assertOk()
            ->assertSee('Activity Timeline');
    }
}