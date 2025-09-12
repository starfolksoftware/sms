<?php

namespace App\Filament\Resources\DealResource\Pages;

use App\Filament\Resources\DealResource;
use Filament\Resources\Pages\CreateRecord;
use App\Events\DealCreated;

class CreateDeal extends CreateRecord
{
    protected static string $resource = DealResource::class;

    protected function afterCreate(): void
    {
        event(new DealCreated($this->record));
    }
}
