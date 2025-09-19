<?php

namespace App\Filament\Widgets;

use App\DTOs\Period;
use App\Services\DealReportService;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DealsSummaryStats extends BaseStatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $reportService = app(DealReportService::class);
        
        // Get current month by default
        $period = $reportService->periodFromPreset('current_month');
        
        // Get filters from the page (if available)
        $filters = $this->getFilters();
        
        $summary = $reportService->summary(
            period: $period,
            ownerId: $filters['owner_id'] ?? null,
            productId: $filters['product_id'] ?? null,
            source: $filters['source'] ?? null
        );

        return [
            Stat::make('Open Deals', $summary->openCount)
                ->description('Currently active deals')
                ->descriptionIcon('heroicon-o-banknotes', IconPosition::Before)
                ->color('primary'),
                
            Stat::make('Open Value', $this->formatCurrency($summary->openSum))
                ->description('Total pipeline value')
                ->descriptionIcon('heroicon-o-currency-dollar', IconPosition::Before)
                ->color('primary'),
                
            Stat::make('Won This Month', $summary->wonInPeriodCount)
                ->description('Deals closed as won')
                ->descriptionIcon('heroicon-o-trophy', IconPosition::Before)
                ->color('success'),
                
            Stat::make('Won Value (This Month)', $this->formatCurrency($summary->wonInPeriodSum))
                ->description('Revenue this month')
                ->descriptionIcon('heroicon-o-trophy', IconPosition::Before)
                ->color('success'),
                
            Stat::make('Win Rate', $summary->winRate . '%')
                ->description('This month\'s performance')
                ->descriptionIcon('heroicon-o-chart-bar', IconPosition::Before)
                ->color($this->getWinRateColor($summary->winRate)),
        ];
    }

    protected function getFilters(): array
    {
        // This method will be overridden by pages that provide filters
        // For now, return empty array
        return [];
    }

    private function formatCurrency(float $amount): string
    {
        // Assuming USD for now - in a real app this would be configurable
        return '$' . number_format($amount, 2);
    }

    private function getWinRateColor(float $winRate): string
    {
        return match (true) {
            $winRate >= 50 => 'success',
            $winRate >= 25 => 'warning',
            default => 'danger',
        };
    }
}