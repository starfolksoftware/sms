<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DealResource\Pages;
use App\Models\Deal;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
// for Actions & Filters namespace usage
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
                            Forms\Components\Select::make('deal_stage_id')
                                ->label('Stage')
                                ->relationship('dealStage', 'name')
                                ->options(fn () => \App\Models\DealStage::active()->ordered()->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(3),
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
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Deal $record): string => $record->contact?->name ?? 'No Contact'),
                TextColumn::make('contact.name')
                    ->label('Contact')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money(fn (Deal $record): string => $record->currency ?? 'USD')
                    ->sortable(),
                BadgeColumn::make('dealStage.name')
                    ->label('Stage')
                    ->formatStateUsing(fn (string $state): string => $state)
                    ->colors([
                        'secondary' => fn (Deal $record) => $record->dealStage?->slug === 'lead-in',
                        'warning' => fn (Deal $record) => $record->dealStage?->slug === 'qualified',
                        'primary' => fn (Deal $record) => $record->dealStage?->slug === 'proposal-sent',
                        'info' => fn (Deal $record) => $record->dealStage?->slug === 'negotiation',
                        'success' => fn (Deal $record) => $record->dealStage?->is_winning_stage,
                        'danger' => fn (Deal $record) => $record->dealStage?->is_losing_stage,
                    ])
                    ->sortable(),
                BadgeColumn::make('status')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'primary' => 'open',
                        'success' => 'won',
                        'danger' => 'lost',
                    ]),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expected_close_date')
                    ->date()
                    ->sortable()
                    ->label('Expected Close')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->label('Updated')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'won' => 'Won',
                        'lost' => 'Lost',
                    ])
                    ->multiple(),
                SelectFilter::make('deal_stage_id')
                    ->label('Stage')
                    ->relationship('dealStage', 'name')
                    ->options(fn () => \App\Models\DealStage::active()->ordered()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('owner_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('source')
                    ->options([
                        'website_form' => 'Website Form',
                        'meta_ads' => 'Meta Ads',
                        'x' => 'X',
                        'instagram' => 'Instagram',
                        'referral' => 'Referral',
                        'manual' => 'Manual',
                        'other' => 'Other',
                    ])
                    ->multiple(),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Filter::make('expected_close_date')
                    ->form([
                        Forms\Components\DatePicker::make('close_from')
                            ->label('Expected Close From'),
                        Forms\Components\DatePicker::make('close_until')
                            ->label('Expected Close Until'),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): EloquentBuilder {
                        return $query
                            ->when(
                                $data['close_from'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('expected_close_date', '>=', $date),
                            )
                            ->when(
                                $data['close_until'],
                                fn (EloquentBuilder $query, $date): EloquentBuilder => $query->whereDate('expected_close_date', '<=', $date),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->url(fn (Deal $record): string => static::getUrl('view', ['record' => $record]))
                        ->icon('heroicon-m-eye'),
                    Action::make('edit')
                        ->url(fn (Deal $record): string => static::getUrl('edit', ['record' => $record]))
                        ->icon('heroicon-m-pencil-square')
                        ->visible(fn (Deal $record): bool => Gate::allows('update', $record)),
                    Action::make('assign_owner')
                        ->label('Assign Owner')
                        ->icon('heroicon-m-user-plus')
                        ->color('warning')
                        ->visible(fn (Deal $record): bool => Gate::allows('update', $record))
                        ->form([
                            Forms\Components\Select::make('owner_id')
                                ->label('Owner')
                                ->relationship('owner', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (Deal $record, array $data): void {
                            $record->update(['owner_id' => $data['owner_id']]);
                            \Filament\Notifications\Notification::make()
                                ->title('Owner assigned successfully')
                                ->success()
                                ->send();
                        }),
                    Action::make('change_stage')
                        ->label('Change Stage')
                        ->icon('heroicon-m-arrow-right')
                        ->color('info')
                        ->visible(fn (Deal $record): bool => Gate::allows('changeStage', $record))
                        ->form([
                            Forms\Components\Select::make('deal_stage_id')
                                ->label('Stage')
                                ->options(fn () => \App\Models\DealStage::active()->ordered()->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (Deal $record, array $data): void {
                            $oldStage = $record->dealStage?->name ?? 'Unknown';
                            $record->update(['deal_stage_id' => $data['deal_stage_id']]);
                            $newStage = $record->fresh()->dealStage?->name ?? 'Unknown';

                            // Dispatch stage changed event
                            \App\Events\DealStageChanged::dispatch($record, $oldStage, $newStage);

                            \Filament\Notifications\Notification::make()
                                ->title('Stage changed successfully')
                                ->success()
                                ->send();
                        }),
                    Action::make('mark_won')
                        ->label('Mark Won')
                        ->icon('heroicon-o-trophy')
                        ->color('success')
                        ->visible(fn (Deal $record): bool => $record->status === 'open' && Gate::allows('win', $record))
                        ->form([
                            Forms\Components\TextInput::make('won_amount')
                                ->label('Won Amount')
                                ->numeric()
                                ->minValue(0)
                                ->default(fn (Deal $record) => $record->amount)
                                ->helperText('Leave empty to use the original deal amount')
                                ->prefix(fn (Deal $record) => $record->currency),
                        ])
                        ->action(function (Deal $record, array $data): void {
                            $record->markAsWon($data['won_amount'] ?? null);
                            \Filament\Notifications\Notification::make()
                                ->title('Deal marked as won')
                                ->success()
                                ->send();
                        }),
                    Action::make('mark_lost')
                        ->label('Mark Lost')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Deal $record): bool => $record->status === 'open' && Gate::allows('lose', $record))
                        ->form([
                            Forms\Components\Textarea::make('lost_reason')
                                ->label('Reason for Loss')
                                ->required()
                                ->minLength(5)
                                ->rows(3)
                                ->placeholder('Please provide a reason why this deal was lost...'),
                        ])
                        ->action(function (Deal $record, array $data): void {
                            $record->markAsLost($data['lost_reason']);
                            \Filament\Notifications\Notification::make()
                                ->title('Deal marked as lost')
                                ->body("Reason: {$data['lost_reason']}")
                                ->warning()
                                ->send();
                        }),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->searchOnBlur()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(12)->schema([
                // Header section with key deal information
                \Filament\Schemas\Components\Section::make('Deal Overview')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(3)->schema([
                            TextEntry::make('title')
                                ->weight('bold')
                                ->size('lg')
                                ->columnSpan(2),
                            TextEntry::make('amount')
                                ->money(fn (Deal $record): string => $record->currency ?? 'USD')
                                ->label('Amount')
                                ->size('lg')
                                ->weight('bold')
                                ->color('primary'),
                        ]),
                        \Filament\Schemas\Components\Grid::make(4)->schema([
                            TextEntry::make('dealStage.name')
                                ->label('Stage')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => $state)
                                ->colors([
                                    'secondary' => fn (Deal $record) => $record->dealStage?->slug === 'lead-in',
                                    'warning' => fn (Deal $record) => $record->dealStage?->slug === 'qualified',
                                    'primary' => fn (Deal $record) => $record->dealStage?->slug === 'proposal-sent',
                                    'info' => fn (Deal $record) => $record->dealStage?->slug === 'negotiation',
                                    'success' => fn (Deal $record) => $record->dealStage?->is_winning_stage,
                                    'danger' => fn (Deal $record) => $record->dealStage?->is_losing_stage,
                                ]),
                            TextEntry::make('status')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => ucfirst($state))
                                ->colors([
                                    'primary' => 'open',
                                    'success' => 'won',
                                    'danger' => 'lost',
                                ]),
                            TextEntry::make('source')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_')))
                                ->color('info'),
                            TextEntry::make('probability')
                                ->suffix('%')
                                ->label('Probability')
                                ->placeholder('Not set'),
                        ]),
                    ])
                    ->columnSpan(12),

                // Contact and Product information
                \Filament\Schemas\Components\Section::make('Associations')
                    ->schema([
                        TextEntry::make('contact.name')
                            ->label('Contact')
                            ->url(fn (Deal $record): ?string => $record->contact ? route('filament.admin.resources.contacts.view', $record->contact) : null)
                            ->color('primary')
                            ->icon('heroicon-m-user')
                            ->placeholder('No contact assigned'),
                        TextEntry::make('contact.email')
                            ->label('Contact Email')
                            ->copyable()
                            ->placeholder('No email'),
                        TextEntry::make('product.name')
                            ->label('Product')
                            ->url(fn (Deal $record): ?string => $record->product ? route('filament.admin.resources.products.view', $record->product) : null)
                            ->color('primary')
                            ->icon('heroicon-m-cube')
                            ->placeholder('No product assigned'),
                        TextEntry::make('owner.name')
                            ->label('Owner')
                            ->icon('heroicon-m-user-circle')
                            ->placeholder('No owner assigned'),
                    ])
                    ->columns(2)
                    ->columnSpan(6),

                // Key dates and metrics
                \Filament\Schemas\Components\Section::make('Timeline & Metrics')
                    ->schema([
                        TextEntry::make('expected_close_date')
                            ->date()
                            ->label('Expected Close')
                            ->icon('heroicon-m-calendar')
                            ->placeholder('Not set'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created'),
                        TextEntry::make('updated_at')
                            ->since()
                            ->label('Last Updated'),
                        TextEntry::make('closed_at')
                            ->dateTime()
                            ->label('Closed At')
                            ->placeholder('Not closed')
                            ->visible(fn (Deal $record): bool => in_array($record->status, ['won', 'lost'])),
                    ])
                    ->columns(2)
                    ->columnSpan(6),

                // Won/Lost details if applicable
                \Filament\Schemas\Components\Section::make('Outcome Details')
                    ->schema([
                        TextEntry::make('won_amount')
                            ->money(fn (Deal $record): string => $record->currency ?? 'USD')
                            ->label('Won Amount')
                            ->icon('heroicon-m-trophy')
                            ->color('success')
                            ->visible(fn (Deal $record): bool => $record->status === 'won'),
                        TextEntry::make('lost_reason')
                            ->label('Lost Reason')
                            ->icon('heroicon-m-x-circle')
                            ->color('danger')
                            ->visible(fn (Deal $record): bool => $record->status === 'lost'),
                    ])
                    ->columns(1)
                    ->columnSpan(6)
                    ->visible(fn (Deal $record): bool => in_array($record->status, ['won', 'lost'])),

                // Notes section
                \Filament\Schemas\Components\Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')
                            ->markdown()
                            ->placeholder('No notes recorded.')
                            ->columnSpanFull(),
                        TextEntry::make('source_meta')
                            ->label('Source Metadata')
                            ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'None')
                            ->placeholder('None')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpan(12),
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
        return [
            \App\Filament\Resources\DealResource\RelationManagers\ActivitiesRelationManager::class,
            \App\Filament\Resources\DealResource\RelationManagers\TasksRelationManager::class,
        ];
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
