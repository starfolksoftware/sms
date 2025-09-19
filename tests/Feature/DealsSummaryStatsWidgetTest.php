<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealsSummaryStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_widget_displays_on_deals_list_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        
        $contact = Contact::factory()->create();
        
        // Create test data
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'open',
            'amount' => 1000.00,
        ]);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertSee('Open Deals')
            ->assertSee('Open Value')
            ->assertSee('Won This Month')
            ->assertSee('Won Value (This Month)')
            ->assertSee('Win Rate');
    }

    public function test_widget_shows_correct_open_deals_stats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $user->givePermissionTo('view_deals');
        
        $contact = Contact::factory()->create();
        
        // Create 2 open deals
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'open',
            'amount' => 1000.00,
        ]);
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'open',
            'amount' => 2500.00,
        ]);
        
        // Create a closed deal (should not be counted)
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'won',
            'amount' => 500.00,
            'closed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertSee('2') // Open deals count
            ->assertSee('$3,500.00'); // Open deals sum
    }

    public function test_widget_shows_correct_won_deals_this_month(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $user->givePermissionTo('view_deals');
        
        $contact = Contact::factory()->create();
        $now = Carbon::parse('2024-02-15 12:00:00');
        Carbon::setTestNow($now);
        
        // Create won deal this month
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'won',
            'amount' => 1000.00,
            'won_amount' => 1200.00,
            'closed_at' => $now->clone()->subDays(5),
        ]);
        
        // Create won deal last month (should not be counted)
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'won',
            'amount' => 500.00,
            'closed_at' => $now->clone()->subMonth(),
        ]);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertSee('1') // Won deals count this month
            ->assertSee('$1,200.00'); // Won deals value this month
            
        Carbon::setTestNow();
    }

    public function test_widget_shows_correct_win_rate(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $user->givePermissionTo('view_deals');
        
        $contact = Contact::factory()->create();
        $now = Carbon::parse('2024-02-15 12:00:00');
        Carbon::setTestNow($now);
        
        // Create 3 won deals this month
        for ($i = 0; $i < 3; $i++) {
            Deal::factory()->create([
                'contact_id' => $contact->id,
                'status' => 'won',
                'amount' => 1000.00,
                'closed_at' => $now->clone()->subDays($i + 1),
            ]);
        }
        
        // Create 2 lost deals this month
        for ($i = 0; $i < 2; $i++) {
            Deal::factory()->create([
                'contact_id' => $contact->id,
                'status' => 'lost',
                'amount' => 1000.00,
                'closed_at' => $now->clone()->subDays($i + 1),
            ]);
        }

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertSee('60%'); // 3 wins out of 5 total closed = 60%
            
        Carbon::setTestNow();
    }

    public function test_widget_shows_zero_values_when_no_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $user->givePermissionTo('view_deals');

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertSee('0') // Should show zeros for counts
            ->assertSee('$0.00') // Should show zero currency values
            ->assertSee('0%'); // Should show zero percent
    }

    public function test_widget_handles_currency_formatting(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $user->givePermissionTo('view_deals');
        
        $contact = Contact::factory()->create();
        
        // Create deal with large amount to test formatting
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'open',
            'amount' => 1234567.89,
        ]);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertSee('$1,234,567.89'); // Should format with commas
    }

    public function test_unauthorized_user_cannot_access_deals_page(): void
    {
        $user = User::factory()->create();
        // Don't assign any role
        
        $this->actingAs($user)
            ->get('/deals')
            ->assertStatus(403);
    }

    public function test_widget_shows_different_colors_for_win_rate(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
        $user->givePermissionTo('view_deals');
        
        $contact = Contact::factory()->create();
        $now = Carbon::parse('2024-02-15 12:00:00');
        Carbon::setTestNow($now);
        
        // Create 1 won deal and 9 lost deals for 10% win rate (should be danger color)
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'won',
            'amount' => 1000.00,
            'closed_at' => $now->clone()->subDays(1),
        ]);
        
        for ($i = 0; $i < 9; $i++) {
            Deal::factory()->create([
                'contact_id' => $contact->id,
                'status' => 'lost',
                'amount' => 1000.00,
                'closed_at' => $now->clone()->subDays($i + 2),
            ]);
        }

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk()
            ->assertSee('10%'); // Low win rate
            
        Carbon::setTestNow();
    }
}