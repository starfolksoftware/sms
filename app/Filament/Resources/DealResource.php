<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DealResource\Pages;
use App\Models\Deal;
use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Resource;
use Filament\Tables; // for Actions & Filters namespace usage
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

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
            Forms\Components\TextInput::make('title')->required()->maxLength(240),
            Forms\Components\Select::make('contact_id')->relationship('contact','name')->searchable()->preload()->required(),
            Forms\Components\Select::make('product_id')->relationship('product','name')->searchable()->preload()->nullable(),
            Forms\Components\Select::make('owner_id')->relationship('owner','name')->searchable()->preload()->nullable(),
            Forms\Components\Select::make('stage')->options([
                'new'=>'New','qualified'=>'Qualified','proposal'=>'Proposal','negotiation'=>'Negotiation','closed'=>'Closed'
            ])->default('new')->required(),
            Forms\Components\Select::make('status')->options([
                'open'=>'Open','won'=>'Won','lost'=>'Lost'
            ])->default('open')->disabled(fn ($livewire) => $livewire instanceof Pages\CreateDeal),
            Forms\Components\DatePicker::make('expected_close_date'),
            Forms\Components\TextInput::make('probability')->numeric()->minValue(0)->maxValue(100),
            Forms\Components\TextInput::make('amount')->numeric()->minValue(0),
            Forms\Components\TextInput::make('currency')->maxLength(3)->default('USD')->required(),
            Forms\Components\Select::make('source')->options([
                'website_form'=>'Website form','meta_ads'=>'Meta ads','x'=>'X','instagram'=>'Instagram','referral'=>'Referral','manual'=>'Manual','other'=>'Other'
            ])->default('manual')->required(),
            Forms\Components\Textarea::make('source_meta')->rows(2)->helperText('JSON key/value (optional)')->nullable(),
            Forms\Components\Textarea::make('notes')->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q->with(['contact','owner','product']))
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->weight('bold'),
                TextColumn::make('contact.name')->label('Contact')->sortable()->searchable(),
                TextColumn::make('owner.name')->label('Owner')->sortable()->toggleable(),
                TextColumn::make('product.name')->label('Product')->toggleable(),
                BadgeColumn::make('stage'),
                BadgeColumn::make('status'),
                TextColumn::make('amount')->numeric(2)->label('Amount')->sortable(),
                TextColumn::make('expected_close_date')->date()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('stage')->options([
                    'new'=>'New','qualified'=>'Qualified','proposal'=>'Proposal','negotiation'=>'Negotiation','closed'=>'Closed'
                ]),
                SelectFilter::make('status')->options([
                    'open'=>'Open','won'=>'Won','lost'=>'Lost'
                ]),
                SelectFilter::make('owner_id')->relationship('owner','name')->searchable(),
                SelectFilter::make('contact_id')->relationship('contact','name')->searchable(),
                Filter::make('expected_close_between')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])->query(fn (Builder $q, array $d) =>
                        $q->when($d['from'] ?? null, fn($qq,$v)=>$qq->whereDate('expected_close_date','>=',$v))
                          ->when($d['to'] ?? null, fn($qq,$v)=>$qq->whereDate('expected_close_date','<=',$v))
                    ),
                TrashedFilter::make(),
            ])
            ->defaultSort('created_at','desc')
            ->persistFiltersInSession();
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
        return ['title','contact.name'];
    }
}
