<?php

namespace App\Filament\Resources\DealStages;

use App\Filament\Resources\DealStages\Pages\ManageDealStages;
use App\Models\DealStage;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\Auth;

class DealStageResource extends Resource
{
    protected static ?string $model = DealStage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $modelLabel = 'Deal Stage';

    protected static ?string $pluralModelLabel = 'Deal Stages';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('manage_stages') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(DealStage::class, 'slug', ignoreRecord: true)
                    ->rules(['alpha_dash']),

                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->minValue(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Forms\Components\Toggle::make('is_winning_stage')
                    ->label('Winning Stage')
                    ->helperText('Mark this stage as a winning/closed won stage'),

                Forms\Components\Toggle::make('is_losing_stage')
                    ->label('Losing Stage')
                    ->helperText('Mark this stage as a losing/closed lost stage'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order')
                    ->numeric()
                    ->sortable(),

                BadgeColumn::make('is_active')
                    ->label('Status')
                    ->getStateUsing(fn (DealStage $record) => $record->is_active ? 'Active' : 'Inactive')
                    ->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),

                BadgeColumn::make('type')
                    ->getStateUsing(function (DealStage $record) {
                        if ($record->is_winning_stage) return 'Winning';
                        if ($record->is_losing_stage) return 'Losing';
                        return 'Regular';
                    })
                    ->color(function (string $state) {
                        return match ($state) {
                            'Winning' => 'success',
                            'Losing' => 'danger',
                            default => 'primary',
                        };
                    }),

                TextColumn::make('deals_count')
                    ->counts('deals')
                    ->label('Deals Count'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function (DealStage $record) {
                        if ($record->deals()->count() > 0) {
                            throw new \Exception('Cannot delete stage with existing deals. Archive it instead.');
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDealStages::route('/'),
        ];
    }
}
