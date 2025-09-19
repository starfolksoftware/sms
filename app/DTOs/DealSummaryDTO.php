<?php

namespace App\DTOs;

class DealSummaryDTO
{
    public function __construct(
        public readonly int $openCount,
        public readonly float $openSum,
        public readonly int $wonInPeriodCount,
        public readonly float $wonInPeriodSum,
        public readonly float $winRate,
        public readonly Period $period
    ) {}

    public function toArray(): array
    {
        return [
            'open' => [
                'count' => $this->openCount,
                'sum' => $this->openSum,
            ],
            'won_in_period' => [
                'count' => $this->wonInPeriodCount,
                'sum' => $this->wonInPeriodSum,
            ],
            'win_rate' => $this->winRate,
            'period' => $this->period->toArray(),
        ];
    }
}