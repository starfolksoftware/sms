<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('timeline')
                ->label('View Timeline')
                ->icon('heroicon-o-clock')
                ->url(fn () => static::getResource()::getUrl('timeline', ['record' => $this->getRecord()]))
                ->color('gray'),
            EditAction::make(),
        ];
    }
}
