<?php

namespace App\Filament\Resources;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
// already imported but ensure actions autoload
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static \UnitEnum|string|null $navigationGroup = 'CRM';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(12)->schema([
                \Filament\Schemas\Components\Section::make('Identity')
                    ->description('Basic personal details')
                    ->schema([
                        TextInput::make('first_name')->maxLength(120)->columnSpan(6),
                        TextInput::make('last_name')->maxLength(120)->columnSpan(6),
                        TextInput::make('name')->maxLength(240)->helperText('Auto-filled from first/last if empty.')->columnSpanFull(),
                        TextInput::make('email')
                            ->email()->maxLength(255)->nullable()
                            ->live(onBlur: true)
                            ->rule(fn ($record) => Rule::unique('contacts', 'email_normalized')->ignore($record))
                            ->afterStateUpdated(function ($state, callable $get) {
                                if (! $state) {
                                    return;
                                }
                                $id = $get('id');
                                $exists = Contact::query()
                                    ->when($id, fn ($q) => $q->where('id', '!=', $id))
                                    ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($state))])
                                    ->exists();
                                if ($exists) {
                                    Notification::make()->warning()->title('Possible duplicate')->body('A contact with this email already exists.')->persistent()->send();
                                }
                            })
                            ->columnSpan(6),
                        TextInput::make('phone')->maxLength(32)->tel()->columnSpan(6),
                    ])->columns(12)->columnSpan(8),

                \Filament\Schemas\Components\Section::make('Business')
                    ->description('Organisation & role')
                    ->schema([
                        TextInput::make('company')->maxLength(180)->columnSpan(6),
                        TextInput::make('job_title')->maxLength(180)->columnSpan(6),
                        Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload()->nullable()->columnSpanFull(),
                    ])->columns(12)->columnSpan(4),

                \Filament\Schemas\Components\Section::make('Status & Source')
                    ->schema([
                        Select::make('status')
                            ->options(collect(ContactStatus::cases())->mapWithKeys(fn ($c) => [$c->value => ucfirst($c->value)]))
                            ->native(false)->required()->default(ContactStatus::Lead->value)->columnSpan(6),
                        Select::make('source')
                            ->options([
                                'website_form' => 'Website form', 'meta_ads' => 'Meta ads', 'x' => 'X',
                                'instagram' => 'Instagram', 'referral' => 'Referral', 'manual' => 'Manual', 'other' => 'Other',
                            ])->native(false)->required()->default('manual')->columnSpan(6),
                        Textarea::make('source_meta')->rows(2)->helperText('JSON key/value (optional)')->nullable()->columnSpanFull(),
                    ])->columns(12)->columnSpan(8),

                \Filament\Schemas\Components\Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')->rows(6)->placeholder('Internal notes...')->columnSpanFull(),
                    ])->collapsible()->collapsed()->columnSpan(4),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('email')->searchable()->icon('heroicon-m-envelope'),
                BadgeColumn::make('status')->colors([
                    'warning' => 'lead',
                    'info' => 'qualified',
                    'success' => 'customer',
                    'gray' => 'archived',
                ])->sortable(),
                TextColumn::make('owner.name')->label('Owner')->sortable(),
                TextColumn::make('created_at')->since()->label('Created'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'lead' => 'Lead', 'qualified' => 'Qualified', 'customer' => 'Customer', 'archived' => 'Archived',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [\App\Filament\Resources\ContactResource\RelationManagers\DealsRelationManager::class];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(12)->schema([
                \Filament\Schemas\Components\Section::make('Identity')
                    ->schema([
                        TextEntry::make('name')->label('Full Name')->weight('bold')->size('lg'),
                        TextEntry::make('email')->icon('heroicon-m-envelope')->copyable(),
                        TextEntry::make('phone')->icon('heroicon-m-phone')->badge()->color('info'),
                    ])->columns(3)->columnSpan(8),
                \Filament\Schemas\Components\Section::make('Business')
                    ->schema([
                        TextEntry::make('company'),
                        TextEntry::make('job_title')->label('Job Title'),
                        TextEntry::make('owner.name')->label('Owner'),
                    ])->columns(1)->columnSpan(4),
                \Filament\Schemas\Components\Section::make('Status')
                    ->schema([
                        TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => ucfirst($state)),
                        TextEntry::make('source')->badge()->label('Source')->formatStateUsing(fn ($state) => str_replace('_', ' ', ucfirst($state))),
                        TextEntry::make('created_at')->dateTime()->label('Created'),
                        TextEntry::make('updated_at')->since()->label('Updated'),
                    ])->columns(2)->columnSpan(6),
                \Filament\Schemas\Components\Section::make('Notes')
                    ->schema([
                        TextEntry::make('notes')->markdown()->default('No notes recorded.'),
                        TextEntry::make('source_meta')->label('Source Meta')->default('-'),
                    ])->collapsed()->collapsible()->columnSpan(6),
            ])->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'company', 'phone'];
    }

    public static function getEloquentQuery(): Builder
    {
        return Contact::query()->with('owner');
    }
}
