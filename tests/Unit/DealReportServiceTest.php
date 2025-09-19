<?php

namespace Tests\Unit;

use App\DTOs\Period;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\User;
use App\Services\DealReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private DealReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DealReportService();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_calculates_open_deals_correctly(): void
    {
        $contact = Contact::factory()->create();
        
        // Create open deals
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'open',
            'amount' => 1000.00,
        ]);
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'open',
            'amount' => 2000.00,
        ]);
        
        // Create closed deal (should not be counted)
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'won',
            'amount' => 500.00,
            'closed_at' => now(),
        ]);

        $period = Period::currentMonth();
        $summary = $this->service->summary($period);

        $this->assertEquals(2, $summary->openCount);
        $this->assertEquals(3000.00, $summary->openSum);
    }

    public function test_calculates_won_deals_in_period_correctly(): void
    {
        $contact = Contact::factory()->create();
        $now = Carbon::parse('2024-02-15 12:00:00');
        Carbon::setTestNow($now);
        
        // Create won deal in current month
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'won',
            'amount' => 1000.00,
            'won_amount' => 1200.00,
            'closed_at' => $now->clone()->subDays(5),
        ]);
        
        // Create won deal in previous month (should not be counted)
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'won',
            'amount' => 500.00,
            'closed_at' => $now->clone()->subMonth(),
        ]);
        
        // Create open deal (should not be counted)
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'open',
            'amount' => 800.00,
        ]);

        $period = Period::currentMonth($now);
        $summary = $this->service->summary($period);

        $this->assertEquals(1, $summary->wonInPeriodCount);
        $this->assertEquals(1200.00, $summary->wonInPeriodSum);
        
        Carbon::setTestNow();
    }

    public function test_uses_amount_when_won_amount_is_null(): void
    {
        $contact = Contact::factory()->create();
        $now = Carbon::parse('2024-02-15 12:00:00');
        Carbon::setTestNow($now);
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'won',
            'amount' => 1000.00,
            'won_amount' => null,
            'closed_at' => $now->clone()->subDays(5),
        ]);

        $period = Period::currentMonth($now);
        $summary = $this->service->summary($period);

        $this->assertEquals(1000.00, $summary->wonInPeriodSum);
        
        Carbon::setTestNow();
    }

    public function test_calculates_win_rate_correctly(): void
    {
        $contact = Contact::factory()->create();
        $now = Carbon::parse('2024-02-15 12:00:00');
        Carbon::setTestNow($now);
        
        // Create 3 won deals and 2 lost deals in current month
        for ($i = 0; $i < 3; $i++) {
            Deal::factory()->create([
                'contact_id' => $contact->id,
                'status' => 'won',
                'amount' => 1000.00,
                'closed_at' => $now->clone()->subDays($i + 1),
            ]);
        }
        
        for ($i = 0; $i < 2; $i++) {
            Deal::factory()->create([
                'contact_id' => $contact->id,
                'status' => 'lost',
                'amount' => 1000.00,
                'closed_at' => $now->clone()->subDays($i + 1),
            ]);
        }
        
        // Create deals outside the period (should not affect win rate)
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'won',
            'closed_at' => $now->clone()->subMonth(),
        ]);

        $period = Period::currentMonth($now);
        $summary = $this->service->summary($period);

        // 3 wins out of 5 total closed = 60%
        $this->assertEquals(60.0, $summary->winRate);
        
        Carbon::setTestNow();
    }

    public function test_win_rate_is_zero_when_no_closed_deals(): void
    {
        $contact = Contact::factory()->create();
        
        // Create only open deals
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'open',
            'amount' => 1000.00,
        ]);

        $period = Period::currentMonth();
        $summary = $this->service->summary($period);

        $this->assertEquals(0.0, $summary->winRate);
    }

    public function test_filters_by_owner(): void
    {
        $contact = Contact::factory()->create();
        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'owner_id' => $owner1->id,
            'status' => 'open',
            'amount' => 1000.00,
        ]);
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'owner_id' => $owner2->id,
            'status' => 'open',
            'amount' => 2000.00,
        ]);

        $period = Period::currentMonth();
        $summary = $this->service->summary($period, ownerId: $owner1->id);

        $this->assertEquals(1, $summary->openCount);
        $this->assertEquals(1000.00, $summary->openSum);
    }

    public function test_filters_by_product(): void
    {
        $contact = Contact::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'product_id' => $product1->id,
            'status' => 'open',
            'amount' => 1000.00,
        ]);
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'product_id' => $product2->id,
            'status' => 'open',
            'amount' => 2000.00,
        ]);

        $period = Period::currentMonth();
        $summary = $this->service->summary($period, productId: $product1->id);

        $this->assertEquals(1, $summary->openCount);
        $this->assertEquals(1000.00, $summary->openSum);
    }

    public function test_filters_by_source(): void
    {
        $contact = Contact::factory()->create();
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'source' => 'website_form',
            'status' => 'open',
            'amount' => 1000.00,
        ]);
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'source' => 'referral',
            'status' => 'open',
            'amount' => 2000.00,
        ]);

        $period = Period::currentMonth();
        $summary = $this->service->summary($period, source: 'website_form');

        $this->assertEquals(1, $summary->openCount);
        $this->assertEquals(1000.00, $summary->openSum);
    }

    public function test_period_from_preset(): void
    {
        $now = Carbon::parse('2024-02-15 12:00:00');
        
        $currentMonth = $this->service->periodFromPreset('current_month', $now);
        $this->assertEquals('2024-02-01', $currentMonth->from->toDateString());
        $this->assertEquals('2024-02-29', $currentMonth->to->toDateString());
        
        $last7Days = $this->service->periodFromPreset('last_7_days', $now);
        $this->assertEquals('2024-02-09', $last7Days->from->toDateString());
        $this->assertEquals('2024-02-15', $last7Days->to->toDateString());
        
        $last30Days = $this->service->periodFromPreset('last_30_days', $now);
        $this->assertEquals('2024-01-17', $last30Days->from->toDateString());
        $this->assertEquals('2024-02-15', $last30Days->to->toDateString());
        
        $currentQuarter = $this->service->periodFromPreset('current_quarter', $now);
        $this->assertEquals('2024-01-01', $currentQuarter->from->toDateString());
        $this->assertEquals('2024-03-31', $currentQuarter->to->toDateString());
        
        // Test default fallback
        $default = $this->service->periodFromPreset('invalid_preset', $now);
        $this->assertEquals('2024-02-01', $default->from->toDateString());
        $this->assertEquals('2024-02-29', $default->to->toDateString());
    }
}