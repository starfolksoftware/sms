<?php

namespace App\Filament\Resources;

use App\Enums\ContactStatus;
use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables; // already imported but ensure actions autoload
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
            TextInput::make('first_name')->maxLength(120),
            TextInput::make('last_name')->maxLength(120),
            TextInput::make('name')->maxLength(240)->helperText('Auto-filled from first/last if empty.'),
            TextInput::make('email')
                    ->email()->maxLength(255)->nullable()
                    ->live(onBlur: true)
                    ->rule(fn ($record) => Rule::unique('contacts','email_normalized')->ignore($record))
                    ->afterStateUpdated(function ($state, callable $get) {
                        if (! $state) return;
                        $id = $get('id');
                        $exists = Contact::query()
                            ->when($id, fn ($q) => $q->where('id','!=',$id))
                            ->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($state))])
                            ->exists();
                        if ($exists) {
                            Notification::make()->warning()->title('Possible duplicate')->body('A contact with this email already exists.')->persistent()->send();
                        }
                    }),
            TextInput::make('phone')->maxLength(32),
            TextInput::make('company')->maxLength(180),
            TextInput::make('job_title')->maxLength(180),
            Select::make('status')
                ->options(collect(ContactStatus::cases())->mapWithKeys(fn($c)=>[$c->value => ucfirst($c->value)]))
                ->native(false)->required()->default(ContactStatus::Lead->value),
            Select::make('source')
                ->options([
                    'website_form'=>'Website form','meta_ads'=>'Meta ads','x'=>'X',
                    'instagram'=>'Instagram','referral'=>'Referral','manual'=>'Manual','other'=>'Other'
                ])->native(false)->required()->default('manual'),
            Textarea::make('source_meta')->rows(2)->helperText('JSON key/value (optional)')->nullable(),
            Select::make('owner_id')->relationship('owner','name')->searchable()->preload()->nullable(),
            Textarea::make('notes')->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
    return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('email')->searchable()->icon('heroicon-m-envelope'),
                TextColumn::make('phone')->toggleable(),
                TextColumn::make('company')->searchable()->toggleable(),
                BadgeColumn::make('status')->colors([
                    'warning' => 'lead',
                    'info' => 'qualified',
                    'success' => 'customer',
                    'gray' => 'archived'
                ])->sortable(),
                TextColumn::make('source')->badge()->sortable()->toggleable(),
                TextColumn::make('owner.name')->label('Owner')->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'lead'=>'Lead','qualified'=>'Qualified','customer'=>'Customer','archived'=>'Archived',
                ]),
                SelectFilter::make('source')->options([
                    'website_form'=>'Website form','meta_ads'=>'Meta ads','x'=>'X','instagram'=>'Instagram',
                    'referral'=>'Referral','manual'=>'Manual','other'=>'Other',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
    return [\App\Filament\Resources\ContactResource\RelationManagers\DealsRelationManager::class];
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
        return ['name','email','company','phone'];
    }

    public static function getEloquentQuery(): Builder
    {
        return Contact::query()->with('owner');
    }
}
