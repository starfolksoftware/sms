<?php

namespace App\Filament\Resources\DealResource\Pages;

use App\Filament\Resources\DealResource;
use Filament\Resources\Pages\EditRecord;
use App\Events\DealUpdated;

class EditDeal extends EditRecord
{
    protected static string $resource = DealResource::class;

    protected function afterSave(): void
    {
        event(new DealUpdated($this->record));
    }
}
