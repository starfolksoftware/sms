<?php

namespace App\Services;

use App\DTOs\DealSummaryDTO;
use App\DTOs\Period;
use App\Models\Deal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DealReportService
{
    public function summary(
        Period $period, 
        ?int $ownerId = null, 
        ?int $productId = null, 
        ?string $source = null
    ): DealSummaryDTO {
        $cacheKey = $this->buildCacheKey($period, $ownerId, $productId, $source);
        
        return Cache::remember($cacheKey, 60, function () use ($period, $ownerId, $productId, $source) {
            return $this->calculateSummary($period, $ownerId, $productId, $source);
        });
    }

    public function periodFromPreset(string $preset, ?Carbon $now = null): Period
    {
        return match ($preset) {
            'current_month' => Period::currentMonth($now),
            'last_7_days' => Period::last7Days($now),
            'last_30_days' => Period::last30Days($now),
            'current_quarter' => Period::currentQuarter($now),
            default => Period::currentMonth($now),
        };
    }

    private function calculateSummary(
        Period $period,
        ?int $ownerId,
        ?int $productId, 
        ?string $source
    ): DealSummaryDTO {
        $query = Deal::query();
        
        // Apply filters
        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }
        
        if ($productId) {
            $query->where('product_id', $productId);
        }
        
        if ($source) {
            $query->where('source', $source);
        }

        // Calculate open deals
        $openStats = (clone $query)
            ->where('status', 'open')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as sum')
            ->first();

        $openCount = $openStats->count ?? 0;
        $openSum = $openStats->sum ?? 0.0;

        // Calculate won deals in period
        $wonStats = (clone $query)
            ->where('status', 'won')
            ->whereBetween('closed_at', [$period->from->utc(), $period->to->utc()])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(COALESCE(won_amount, amount)), 0) as sum')
            ->first();

        $wonInPeriodCount = $wonStats->count ?? 0;
        $wonInPeriodSum = $wonStats->sum ?? 0.0;

        // Calculate win rate for the period
        $winRate = $this->calculateWinRate($query, $period);

        return new DealSummaryDTO(
            openCount: $openCount,
            openSum: $openSum,
            wonInPeriodCount: $wonInPeriodCount,
            wonInPeriodSum: $wonInPeriodSum,
            winRate: $winRate,
            period: $period
        );
    }

    private function calculateWinRate(Builder $query, Period $period): float
    {
        $closedInPeriod = (clone $query)
            ->whereIn('status', ['won', 'lost'])
            ->whereBetween('closed_at', [$period->from->utc(), $period->to->utc()])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as wins
            ')
            ->first();

        $total = $closedInPeriod->total ?? 0;
        $wins = $closedInPeriod->wins ?? 0;

        if ($total === 0) {
            return 0.0;
        }

        return round(($wins / $total) * 100, 2);
    }

    private function buildCacheKey(
        Period $period,
        ?int $ownerId,
        ?int $productId,
        ?string $source
    ): string {
        $key = sprintf(
            'deal_summary:%s:%s:%s:%s:%s',
            $period->from->format('Y-m-d'),
            $period->to->format('Y-m-d'),
            $ownerId ?? 'all',
            $productId ?? 'all',
            $source ?? 'all'
        );
        
        return $key;
    }
}