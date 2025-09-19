<?php

namespace App\DTOs;

use Carbon\Carbon;

class Period
{
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to
    ) {}

    public static function currentMonth(?Carbon $now = null): self
    {
        $now = $now ?? now('Africa/Lagos');
        
        return new self(
            $now->clone()->startOfMonth(),
            $now->clone()->endOfMonth()
        );
    }

    public static function last7Days(?Carbon $now = null): self
    {
        $now = $now ?? now('Africa/Lagos');
        
        return new self(
            $now->clone()->subDays(6)->startOfDay(),
            $now->clone()->endOfDay()
        );
    }

    public static function last30Days(?Carbon $now = null): self
    {
        $now = $now ?? now('Africa/Lagos');
        
        return new self(
            $now->clone()->subDays(29)->startOfDay(),
            $now->clone()->endOfDay()
        );
    }

    public static function currentQuarter(?Carbon $now = null): self
    {
        $now = $now ?? now('Africa/Lagos');
        
        return new self(
            $now->clone()->startOfQuarter(),
            $now->clone()->endOfQuarter()
        );
    }

    public static function custom(Carbon $from, Carbon $to): self
    {
        return new self(
            $from->clone()->startOfDay(),
            $to->clone()->endOfDay()
        );
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
        ];
    }

    public function contains(Carbon $date): bool
    {
        return $date->between($this->from, $this->to);
    }
}