<?php

namespace App\Filament\Resources\DealResource\RelationManagers;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity Timeline';

    protected static ?string $label = 'Activity';

    protected static ?string $pluralLabel = 'Activities';

    protected static ?string $recordTitleAttribute = 'description';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('log_name')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace(['_', '.'], ' ', $state)))
                    ->colors([
                        'primary' => fn ($state): bool => str_contains($state, 'created'),
                        'warning' => fn ($state): bool => str_contains($state, 'updated'),
                        'success' => fn ($state): bool => str_contains($state, 'won'),
                        'danger' => fn ($state): bool => str_contains($state, 'lost'),
                        'info' => fn ($state): bool => str_contains($state, 'stage'),
                    ]),
                TextColumn::make('description')
                    ->label('Activity')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->placeholder('System')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('description')
                    ->label('Description'),
                TextEntry::make('log_name')
                    ->label('Type')
                    ->badge(),
                TextEntry::make('causer.name')
                    ->label('User')
                    ->placeholder('System'),
                TextEntry::make('created_at')
                    ->label('Date')
                    ->dateTime(),
                TextEntry::make('properties')
                    ->label('Details')
                    ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'None')
                    ->markdown(),
            ]);
    }
}
