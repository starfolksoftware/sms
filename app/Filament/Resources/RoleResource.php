<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Filament\Resources\RoleResource\Pages;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static \UnitEnum|string|null $navigationGroup = 'Access Control';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
    return Auth::user()?->can('manage_roles') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true)
                ->label('Role Name'),
            CheckboxList::make('permissions')
                ->relationship('permissions', 'name')
                ->options(fn() => Permission::query()->orderBy('name')->pluck('name','name'))
                ->columns(2)
                ->bulkToggleable()
                ->searchable()
                ->label('Permissions'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('permissions_count')
                ->label('Permissions')
                ->state(fn(Role $record) => $record->permissions->count()),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('permissions');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
