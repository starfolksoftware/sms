<?php

namespace Tests\Unit;

use App\DTOs\Period;
use Carbon\Carbon;
use Tests\TestCase;

class PeriodTest extends TestCase
{
    public function test_current_month_creates_correct_period(): void
    {
        $now = Carbon::parse('2024-02-15 14:30:00', 'Africa/Lagos');
        
        $period = Period::currentMonth($now);
        
        $this->assertEquals('2024-02-01 00:00:00', $period->from->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-02-29 23:59:59', $period->to->format('Y-m-d H:i:s'));
    }

    public function test_last_7_days_creates_correct_period(): void
    {
        $now = Carbon::parse('2024-02-15 14:30:00', 'Africa/Lagos');
        
        $period = Period::last7Days($now);
        
        $this->assertEquals('2024-02-09 00:00:00', $period->from->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-02-15 23:59:59', $period->to->format('Y-m-d H:i:s'));
    }

    public function test_last_30_days_creates_correct_period(): void
    {
        $now = Carbon::parse('2024-02-15 14:30:00', 'Africa/Lagos');
        
        $period = Period::last30Days($now);
        
        $this->assertEquals('2024-01-17 00:00:00', $period->from->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-02-15 23:59:59', $period->to->format('Y-m-d H:i:s'));
    }

    public function test_current_quarter_creates_correct_period(): void
    {
        $now = Carbon::parse('2024-02-15 14:30:00', 'Africa/Lagos');
        
        $period = Period::currentQuarter($now);
        
        $this->assertEquals('2024-01-01 00:00:00', $period->from->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-03-31 23:59:59', $period->to->format('Y-m-d H:i:s'));
    }

    public function test_custom_period_creates_correct_period(): void
    {
        $from = Carbon::parse('2024-01-15 10:30:00');
        $to = Carbon::parse('2024-02-20 16:45:00');
        
        $period = Period::custom($from, $to);
        
        $this->assertEquals('2024-01-15 00:00:00', $period->from->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-02-20 23:59:59', $period->to->format('Y-m-d H:i:s'));
    }

    public function test_to_array_returns_correct_format(): void
    {
        $from = Carbon::parse('2024-01-15 10:30:00');
        $to = Carbon::parse('2024-02-20 16:45:00');
        
        $period = Period::custom($from, $to);
        $array = $period->toArray();
        
        $this->assertEquals([
            'from' => '2024-01-15',
            'to' => '2024-02-20',
        ], $array);
    }

    public function test_contains_checks_date_within_period(): void
    {
        $from = Carbon::parse('2024-01-15 00:00:00');
        $to = Carbon::parse('2024-02-20 23:59:59');
        
        $period = Period::custom($from, $to);
        
        // Date within period
        $this->assertTrue($period->contains(Carbon::parse('2024-02-01 12:00:00')));
        
        // Date at start boundary
        $this->assertTrue($period->contains(Carbon::parse('2024-01-15 00:00:00')));
        
        // Date at end boundary
        $this->assertTrue($period->contains(Carbon::parse('2024-02-20 23:59:59')));
        
        // Date before period
        $this->assertFalse($period->contains(Carbon::parse('2024-01-14 23:59:59')));
        
        // Date after period
        $this->assertFalse($period->contains(Carbon::parse('2024-02-21 00:00:01')));
    }

    public function test_leap_year_february(): void
    {
        $now = Carbon::parse('2024-02-15 14:30:00', 'Africa/Lagos'); // 2024 is a leap year
        
        $period = Period::currentMonth($now);
        
        $this->assertEquals('2024-02-29 23:59:59', $period->to->format('Y-m-d H:i:s'));
    }

    public function test_non_leap_year_february(): void
    {
        $now = Carbon::parse('2023-02-15 14:30:00', 'Africa/Lagos'); // 2023 is not a leap year
        
        $period = Period::currentMonth($now);
        
        $this->assertEquals('2023-02-28 23:59:59', $period->to->format('Y-m-d H:i:s'));
    }
}