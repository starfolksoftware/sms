<?php

namespace App\Filament\Resources\WebhookEvents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WebhookEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('event_type')
                    ->label('Event Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Source')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'processed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('attempts')
                    ->label('Attempts')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('received_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->received_at->format('M j, Y g:i A')),
                TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->placeholder('Not processed')
                    ->tooltip(fn ($record) => $record->processed_at?->format('M j, Y g:i A')),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->placeholder('No errors')
                    ->tooltip(fn ($record) => $record->error_message),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'processed' => 'Processed',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('event_type')
                    ->options([
                        'lead_form_submission' => 'Lead Form Submission',
                    ]),
                SelectFilter::make('source')
                    ->options([
                        'website_form' => 'Website Form',
                        'meta_ads' => 'Meta Ads',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->status === 'failed'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('received_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }
}
