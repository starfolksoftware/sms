<?php

namespace App\Filament\Resources\DealResource\Pages;

use App\Filament\Resources\DealResource;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewDeal extends ViewRecord
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_won')
                ->label('Mark Won')
                ->icon('heroicon-o-trophy')
                ->color('success')
                ->visible(fn () => $this->record->status === 'open' && Gate::allows('win', $this->record))
                ->form([
                    Forms\Components\TextInput::make('won_amount')
                        ->label('Won Amount')
                        ->numeric()
                        ->minValue(0)
                        ->default(fn () => $this->record->amount)
                        ->helperText('Leave empty to use the original deal amount')
                        ->prefix(fn () => $this->record->currency),
                ])
                ->action(function (array $data) {
                    $this->record->markAsWon($data['won_amount'] ?? null);

                    Notification::make()
                        ->title('Deal marked as won')
                        ->success()
                        ->send();

                    $this->redirect(route('filament.admin.resources.deals.view', $this->record));
                }),

            Action::make('mark_lost')
                ->label('Mark Lost')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'open' && Gate::allows('lose', $this->record))
                ->form([
                    Forms\Components\Textarea::make('lost_reason')
                        ->label('Reason for Loss')
                        ->required()
                        ->minLength(5)
                        ->rows(3)
                        ->placeholder('Please provide a reason why this deal was lost...'),
                ])
                ->action(function (array $data) {
                    $this->record->markAsLost($data['lost_reason']);

                    Notification::make()
                        ->title('Deal marked as lost')
                        ->body("Reason: {$data['lost_reason']}")
                        ->warning()
                        ->send();

                    $this->redirect(route('filament.admin.resources.deals.view', $this->record));
                }),
        ];
    }
}
