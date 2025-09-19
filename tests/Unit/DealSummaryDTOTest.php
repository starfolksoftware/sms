<?php

namespace Tests\Unit;

use App\DTOs\DealSummaryDTO;
use App\DTOs\Period;
use Carbon\Carbon;
use Tests\TestCase;

class DealSummaryDTOTest extends TestCase
{
    public function test_constructs_with_correct_values(): void
    {
        $period = Period::custom(
            Carbon::parse('2024-01-01'),
            Carbon::parse('2024-01-31')
        );
        
        $dto = new DealSummaryDTO(
            openCount: 5,
            openSum: 10000.50,
            wonInPeriodCount: 3,
            wonInPeriodSum: 7500.25,
            winRate: 60.0,
            period: $period
        );
        
        $this->assertEquals(5, $dto->openCount);
        $this->assertEquals(10000.50, $dto->openSum);
        $this->assertEquals(3, $dto->wonInPeriodCount);
        $this->assertEquals(7500.25, $dto->wonInPeriodSum);
        $this->assertEquals(60.0, $dto->winRate);
        $this->assertSame($period, $dto->period);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $period = Period::custom(
            Carbon::parse('2024-01-01'),
            Carbon::parse('2024-01-31')
        );
        
        $dto = new DealSummaryDTO(
            openCount: 5,
            openSum: 10000.50,
            wonInPeriodCount: 3,
            wonInPeriodSum: 7500.25,
            winRate: 60.0,
            period: $period
        );
        
        $expected = [
            'open' => [
                'count' => 5,
                'sum' => 10000.50,
            ],
            'won_in_period' => [
                'count' => 3,
                'sum' => 7500.25,
            ],
            'win_rate' => 60.0,
            'period' => [
                'from' => '2024-01-01',
                'to' => '2024-01-31',
            ],
        ];
        
        $this->assertEquals($expected, $dto->toArray());
    }

    public function test_to_array_with_zero_values(): void
    {
        $period = Period::custom(
            Carbon::parse('2024-01-01'),
            Carbon::parse('2024-01-31')
        );
        
        $dto = new DealSummaryDTO(
            openCount: 0,
            openSum: 0.0,
            wonInPeriodCount: 0,
            wonInPeriodSum: 0.0,
            winRate: 0.0,
            period: $period
        );
        
        $expected = [
            'open' => [
                'count' => 0,
                'sum' => 0.0,
            ],
            'won_in_period' => [
                'count' => 0,
                'sum' => 0.0,
            ],
            'win_rate' => 0.0,
            'period' => [
                'from' => '2024-01-01',
                'to' => '2024-01-31',
            ],
        ];
        
        $this->assertEquals($expected, $dto->toArray());
    }
}