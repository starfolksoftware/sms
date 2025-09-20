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
            Action::make('edit')
                ->label('Edit Deal')
                ->icon('heroicon-m-pencil-square')
                ->url(fn (): string => static::getResource()::getUrl('edit', ['record' => $this->record]))
                ->visible(fn (): bool => Gate::allows('update', $this->record)),

            Action::make('assign_owner')
                ->label('Assign Owner')
                ->icon('heroicon-m-user-plus')
                ->color('warning')
                ->visible(fn (): bool => Gate::allows('update', $this->record))
                ->form([
                    Forms\Components\Select::make('owner_id')
                        ->label('Owner')
                        ->relationship('owner', 'name')
                        ->searchable()
                        ->preload()
                        ->default(fn () => $this->record->owner_id),
                ])
                ->action(function (array $data): void {
                    $this->record->update(['owner_id' => $data['owner_id']]);
                    Notification::make()
                        ->title('Owner assigned successfully')
                        ->success()
                        ->send();
                    $this->refreshFormData(['owner_id']);
                }),

            Action::make('change_stage')
                ->label('Change Stage')
                ->icon('heroicon-m-arrow-right')
                ->color('info')
                ->visible(fn (): bool => Gate::allows('changeStage', $this->record))
                ->form([
                    Forms\Components\Select::make('deal_stage_id')
                        ->label('Stage')
                        ->options(fn () => \App\Models\DealStage::active()->ordered()->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->default(fn () => $this->record->deal_stage_id)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $oldStage = $this->record->dealStage?->name ?? 'Unknown';
                    $this->record->update(['deal_stage_id' => $data['deal_stage_id']]);
                    $newStage = $this->record->fresh()->dealStage?->name ?? 'Unknown';

                    // Dispatch stage changed event
                    \App\Events\DealStageChanged::dispatch($this->record, $oldStage, $newStage);

                    Notification::make()
                        ->title('Stage changed successfully')
                        ->success()
                        ->send();
                    $this->refreshFormData(['deal_stage_id']);
                }),

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
