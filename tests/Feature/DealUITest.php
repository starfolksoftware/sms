<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        \Filament\Facades\Filament::setCurrentPanel('admin');
        
        $user = User::factory()->create();
        $user->assignRole('Sales');
        
        $contact = Contact::factory()->create();
        Deal::factory()->count(5)->create(['contact_id' => $contact->id]);

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\DealResource\Pages\ListDeals::class)
            ->assertStatus(200);
    }

    public function test_can_render_deal_detail_view_with_timeline(): void
    {
        \Filament\Facades\Filament::setCurrentPanel('admin');
        
        $user = User::factory()->create();
        $user->assignRole('Sales');
        
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create(['contact_id' => $contact->id]);

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\DealResource\Pages\ViewDeal::class, ['record' => $deal->id])
            ->assertStatus(200);
    }

    public function test_can_filter_deals_by_status(): void
    {
        \Filament\Facades\Filament::setCurrentPanel('admin');
        
        $user = User::factory()->create();
        $user->assignRole('Sales');
        
        $contact = Contact::factory()->create();
        Deal::factory()->create(['contact_id' => $contact->id, 'status' => 'open']);
        Deal::factory()->create(['contact_id' => $contact->id, 'status' => 'won']);

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\DealResource\Pages\ListDeals::class)
            ->filterTable('status', 'open')
            ->assertCanSeeTableRecords(Deal::where('status', 'open')->get());
    }

    public function test_shows_activities_timeline_for_a_deal(): void
    {
        \Filament\Facades\Filament::setCurrentPanel('admin');
        
        $user = User::factory()->create();
        $user->assignRole('Sales');
        
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create(['contact_id' => $contact->id]);
        
        // Create an activity
        activity()->performedOn($deal)->log('Deal created');

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\DealResource\Pages\ViewDeal::class, ['record' => $deal->id])
            ->assertStatus(200);
    }
}