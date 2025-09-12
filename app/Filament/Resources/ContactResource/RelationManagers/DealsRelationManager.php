<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms;
use Filament\Schemas\Schema;

class DealsRelationManager extends RelationManager
{
    protected static string $relationship = 'deals';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(240),
            Forms\Components\TextInput::make('amount')->numeric()->minValue(0),
            Forms\Components\TextInput::make('currency')->default('USD')->maxLength(3)->required(),
            Forms\Components\Select::make('stage')->options([
                'new'=>'New','qualified'=>'Qualified','proposal'=>'Proposal','negotiation'=>'Negotiation','closed'=>'Closed'
            ])->default('new')->required(),
            Forms\Components\Select::make('owner_id')->relationship('owner','name')->searchable()->preload(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable(),
            BadgeColumn::make('stage'),
            TextColumn::make('amount')->numeric(2),
        ]);
    }
}
