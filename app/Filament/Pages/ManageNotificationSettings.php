<?php

namespace App\Filament\Pages;

use App\Settings\NotificationSettings;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class ManageNotificationSettings extends SettingsPage
{
    protected static string $settings = NotificationSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notification Settings';

    protected static ?string $title = 'Notification Settings';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Deal Created Notifications')
                    ->description('Configure who gets notified when new deals are created')
                    ->schema([
                        Toggle::make('dealCreatedEnabled')
                            ->label('Enable Deal Created Notifications')
                            ->helperText('Turn on/off all deal created notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('dealCreatedEmailEnabled')
                                    ->label('Email Notifications')
                                    ->helperText('Send email notifications'),

                                Toggle::make('dealCreatedDatabaseEnabled')
                                    ->label('In-App Notifications')
                                    ->helperText('Show in-app notifications'),
                            ]),

                        Select::make('dealCreatedRoles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray())
                            ->helperText('Users with these roles will receive notifications'),

                        Select::make('dealCreatedUsers')
                            ->label('Additional Users to Notify')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => \App\Models\User::where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->getOptionLabelsUsing(fn (array $values): array => \App\Models\User::whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->helperText('Specific users who should always receive notifications'),
                    ])
                    ->collapsible(),

                Section::make('Deal Stage Changed Notifications')
                    ->description('Configure who gets notified when deals move between stages')
                    ->schema([
                        Toggle::make('dealStageChangedEnabled')
                            ->label('Enable Stage Change Notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('dealStageChangedEmailEnabled')
                                    ->label('Email Notifications'),

                                Toggle::make('dealStageChangedDatabaseEnabled')
                                    ->label('In-App Notifications'),
                            ]),

                        Select::make('dealStageChangedRoles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray()),

                        Select::make('dealStageChangedUsers')
                            ->label('Additional Users to Notify')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => \App\Models\User::where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->getOptionLabelsUsing(fn (array $values): array => \App\Models\User::whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->helperText('Specific users who should always receive notifications'),
                    ])
                    ->collapsible(),

                Section::make('Deal Won Notifications')
                    ->description('Configure who gets notified when deals are marked as won')
                    ->schema([
                        Toggle::make('dealWonEnabled')
                            ->label('Enable Deal Won Notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('dealWonEmailEnabled')
                                    ->label('Email Notifications'),

                                Toggle::make('dealWonDatabaseEnabled')
                                    ->label('In-App Notifications'),
                            ]),

                        Select::make('dealWonRoles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray()),

                        Select::make('dealWonUsers')
                            ->label('Additional Users to Notify')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => \App\Models\User::where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->getOptionLabelsUsing(fn (array $values): array => \App\Models\User::whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->toArray()
                            ),
                    ])
                    ->collapsible(),

                Section::make('Deal Lost Notifications')
                    ->description('Configure who gets notified when deals are marked as lost')
                    ->schema([
                        Toggle::make('dealLostEnabled')
                            ->label('Enable Deal Lost Notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('dealLostEmailEnabled')
                                    ->label('Email Notifications'),

                                Toggle::make('dealLostDatabaseEnabled')
                                    ->label('In-App Notifications'),
                            ]),

                        Select::make('dealLostRoles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray()),

                        Select::make('dealLostUsers')
                            ->label('Additional Users to Notify')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => \App\Models\User::where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->getOptionLabelsUsing(fn (array $values): array => \App\Models\User::whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->toArray()
                            ),
                    ])
                    ->collapsible(),

                Section::make('Deal Assignment Notifications')
                    ->description('Configure who gets notified when deals are reassigned')
                    ->schema([
                        Toggle::make('dealAssignedEnabled')
                            ->label('Enable Deal Assignment Notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('dealAssignedEmailEnabled')
                                    ->label('Email Notifications'),

                                Toggle::make('dealAssignedDatabaseEnabled')
                                    ->label('In-App Notifications'),
                            ]),

                        Select::make('dealAssignedRoles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray())
                            ->helperText('Deal owner and previous owner are always notified'),

                        Select::make('dealAssignedUsers')
                            ->label('Additional Users to Notify')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => \App\Models\User::where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->getOptionLabelsUsing(fn (array $values): array => \App\Models\User::whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->toArray()
                            ),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function canAccess(): bool
    {
        return Filament::auth()->user()->can('manage_settings') || Filament::auth()->user()->hasRole('Admin');
    }
}
