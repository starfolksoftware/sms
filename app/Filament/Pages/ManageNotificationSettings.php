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
                        Toggle::make('deal_created_enabled')
                            ->label('Enable Deal Created Notifications')
                            ->helperText('Turn on/off all deal created notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('deal_created_email_enabled')
                                    ->label('Email Notifications')
                                    ->helperText('Send email notifications'),

                                Toggle::make('deal_created_database_enabled')
                                    ->label('In-App Notifications')
                                    ->helperText('Show in-app notifications'),
                            ]),

                        Select::make('deal_created_roles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray())
                            ->helperText('Users with these roles will receive notifications'),

                        Select::make('deal_created_users')
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
                        Toggle::make('deal_stage_changed_enabled')
                            ->label('Enable Stage Change Notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('deal_stage_changed_email_enabled')
                                    ->label('Email Notifications'),

                                Toggle::make('deal_stage_changed_database_enabled')
                                    ->label('In-App Notifications'),
                            ]),

                        Select::make('deal_stage_changed_roles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray()),

                        Select::make('deal_stage_changed_users')
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
                        Toggle::make('deal_won_enabled')
                            ->label('Enable Deal Won Notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('deal_won_email_enabled')
                                    ->label('Email Notifications'),

                                Toggle::make('deal_won_database_enabled')
                                    ->label('In-App Notifications'),
                            ]),

                        Select::make('deal_won_roles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray()),

                        Select::make('deal_won_users')
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
                        Toggle::make('deal_lost_enabled')
                            ->label('Enable Deal Lost Notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('deal_lost_email_enabled')
                                    ->label('Email Notifications'),

                                Toggle::make('deal_lost_database_enabled')
                                    ->label('In-App Notifications'),
                            ]),

                        Select::make('deal_lost_roles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray()),

                        Select::make('deal_lost_users')
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
                        Toggle::make('deal_assigned_enabled')
                            ->label('Enable Deal Assignment Notifications'),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('deal_assigned_email_enabled')
                                    ->label('Email Notifications'),

                                Toggle::make('deal_assigned_database_enabled')
                                    ->label('In-App Notifications'),
                            ]),

                        Select::make('deal_assigned_roles')
                            ->label('Notify Users with Roles')
                            ->multiple()
                            ->options(Role::pluck('name', 'name')->toArray())
                            ->helperText('Deal owner and previous owner are always notified'),

                        Select::make('deal_assigned_users')
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
