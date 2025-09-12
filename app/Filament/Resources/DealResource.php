<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DealResource\Pages;
use App\Models\Deal;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
// for Actions & Filters namespace usage
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DealResource extends Resource
{
    protected static ?string $model = Deal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $recordTitleAttribute = 'title';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view_deals') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(12)->schema([
                \Filament\Schemas\Components\Section::make('Deal')
                    ->schema([
                        Forms\Components\TextInput::make('title')->required()->maxLength(240)->columnSpanFull(),
                        \Filament\Schemas\Components\Grid::make(12)->schema([
                            Forms\Components\TextInput::make('amount')->numeric()->minValue(0)->columnSpan(4),
                            Forms\Components\TextInput::make('currency')->maxLength(3)->default('USD')->required()->columnSpan(2),
                            Forms\Components\Select::make('stage')->options([
                                'new' => 'New', 'qualified' => 'Qualified', 'proposal' => 'Proposal', 'negotiation' => 'Negotiation', 'closed' => 'Closed',
                            ])->default('new')->required()->columnSpan(3),
                            Forms\Components\Select::make('status')->options([
                                'open' => 'Open', 'won' => 'Won', 'lost' => 'Lost',
                            ])->default('open')->disabled(fn ($livewire) => $livewire instanceof Pages\CreateDeal)->columnSpan(3),
                        ])->columnSpanFull(),
                        \Filament\Schemas\Components\Grid::make(12)->schema([
                            Forms\Components\DatePicker::make('expected_close_date')->columnSpan(4),
                            Forms\Components\TextInput::make('probability')->numeric()->minValue(0)->maxValue(100)->suffix('%')->columnSpan(4),
                        ])->columnSpanFull(),
                    ])->columns(12)->columnSpan(8),
                \Filament\Schemas\Components\Section::make('Associations')
                    ->schema([
                        Forms\Components\Select::make('contact_id')->relationship('contact', 'name')->searchable()->preload()->required()->columnSpanFull(),
                        Forms\Components\Select::make('product_id')->relationship('product', 'name')->searchable()->preload()->nullable()->columnSpanFull(),
                        Forms\Components\Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload()->nullable()->columnSpanFull(),
                    ])->columns(1)->columnSpan(4),
                \Filament\Schemas\Components\Section::make('Source')
                    ->schema([
                        Forms\Components\Select::make('source')->options([
                            'website_form' => 'Website form', 'meta_ads' => 'Meta ads', 'x' => 'X', 'instagram' => 'Instagram', 'referral' => 'Referral', 'manual' => 'Manual', 'other' => 'Other',
                        ])->default('manual')->required()->columnSpan(6),
                        Forms\Components\Textarea::make('source_meta')->rows(2)->helperText('JSON key/value (optional)')->nullable()->columnSpanFull(),
                    ])->columns(12)->columnSpan(8),
                \Filament\Schemas\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')->rows(6)->placeholder('Internal notes...')->columnSpanFull(),
                    ])->collapsible()->collapsed()->columnSpan(4),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->weight('bold'),
                TextColumn::make('contact.name')->label('Contact')->sortable()->searchable(),
                BadgeColumn::make('stage'),
                BadgeColumn::make('status'),
                TextColumn::make('amount')->numeric(2)->label('Amount')->sortable(),
                TextColumn::make('expected_close_date')->date()->sortable()->label('Expected Close'),
            ])
            ->filters([
                SelectFilter::make('stage')->options([
                    'new' => 'New', 'qualified' => 'Qualified', 'proposal' => 'Proposal', 'negotiation' => 'Negotiation', 'closed' => 'Closed',
                ]),
                SelectFilter::make('status')->options([
                    'open' => 'Open', 'won' => 'Won', 'lost' => 'Lost',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(12)->schema([
                \Filament\Schemas\Components\Section::make('Deal')
                    ->schema([
                        TextEntry::make('title')->weight('bold')->size('lg'),
                        TextEntry::make('amount')->money(fn ($record) => $record->currency ?? 'USD')->label('Amount'),
                        TextEntry::make('probability')->suffix('%')->label('Probability'),
                        TextEntry::make('expected_close_date')->date()->label('Expected Close'),
                    ])->columns(2)->columnSpan(8),
                \Filament\Schemas\Components\Section::make('Status')
                    ->schema([
                        TextEntry::make('stage')->badge()->formatStateUsing(fn ($s) => ucfirst($s)),
                        TextEntry::make('status')->badge()->formatStateUsing(fn ($s) => ucfirst($s)),
                        TextEntry::make('source')->badge()->label('Source'),
                        TextEntry::make('created_at')->dateTime()->label('Created'),
                        TextEntry::make('updated_at')->since()->label('Updated'),
                    ])->columns(2)->columnSpan(4),
                \Filament\Schemas\Components\Section::make('Associations')
                    ->schema([
                        TextEntry::make('contact.name')->label('Contact'),
                        TextEntry::make('product.name')->label('Product'),
                        TextEntry::make('owner.name')->label('Owner'),
                    ])->columns(1)->columnSpan(4),
                \Filament\Schemas\Components\Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')->markdown()->default('No notes recorded.'),
                        TextEntry::make('source_meta')->label('Source Meta')->default('-'),
                    ])->collapsed()->collapsible()->columnSpan(8),
            ])->columnSpanFull(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Ensure a proper Eloquent builder with the model instance is always returned
        // and eager load commonly used relations to prevent N+1 issues.
        return Deal::query()->with(['contact', 'owner', 'product']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeals::route('/'),
            'create' => Pages\CreateDeal::route('/create'),
            'view' => Pages\ViewDeal::route('/{record}'),
            'edit' => Pages\EditDeal::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'contact.name'];
    }
}
