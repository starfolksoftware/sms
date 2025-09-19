<?php

namespace App\Filament\Resources\DealResource\Pages;

use App\Filament\Resources\DealResource;
use App\Filament\Widgets\DealsSummaryStats;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    protected function getTableQuery(): Builder
    {
        return DealResource::getEloquentQuery();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DealsSummaryStats::class,
        ];
    }
}
