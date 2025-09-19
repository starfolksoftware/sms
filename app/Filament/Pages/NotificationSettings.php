<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class NotificationSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected string $view = 'filament.pages.notification-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Notification Settings';

    protected static ?string $navigationLabel = 'Notifications';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('manage_notifications') || auth()->user()->hasRole('Admin');
    }

    public function mount(): void
    {
        // Load current notification settings
        // For now, we'll use default values
        $this->form->fill([
            'deal_created_enabled' => true,
            'deal_stage_changed_enabled' => true,
            'deal_won_enabled' => true,
            'deal_lost_enabled' => true,
            'deal_assigned_enabled' => true,
            'deal_created_roles' => ['Sales Manager'],
            'deal_stage_changed_roles' => ['Sales Manager'],
            'deal_won_roles' => ['Sales Manager'],
            'deal_lost_roles' => ['Sales Manager'],
            'deal_assigned_roles' => [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Deal Notification Settings')
                    ->description('Configure which deal events trigger notifications and who receives them.')
                    ->schema([
                        Toggle::make('deal_created_enabled')
                            ->label('Deal Created Notifications')
                            ->helperText('Notify when a new deal is created'),

                        Select::make('deal_created_roles')
                            ->label('Who receives Deal Created notifications?')
                            ->multiple()
                            ->options([
                                'Sales' => 'Sales Team',
                                'Sales Manager' => 'Sales Managers',
                                'Admin' => 'Administrators',
                            ])
                            ->helperText('Deal owner is always notified (if different from creator)'),

                        Toggle::make('deal_stage_changed_enabled')
                            ->label('Deal Stage Changed Notifications')
                            ->helperText('Notify when a deal moves between stages'),

                        Select::make('deal_stage_changed_roles')
                            ->label('Who receives Deal Stage Changed notifications?')
                            ->multiple()
                            ->options([
                                'Sales' => 'Sales Team',
                                'Sales Manager' => 'Sales Managers',
                                'Admin' => 'Administrators',
                            ])
                            ->helperText('Deal owner is always notified'),

                        Toggle::make('deal_won_enabled')
                            ->label('Deal Won Notifications')
                            ->helperText('Notify when a deal is marked as won'),

                        Select::make('deal_won_roles')
                            ->label('Who receives Deal Won notifications?')
                            ->multiple()
                            ->options([
                                'Sales' => 'Sales Team',
                                'Sales Manager' => 'Sales Managers',
                                'Admin' => 'Administrators',
                                'Finance' => 'Finance Team',
                            ])
                            ->helperText('Deal owner and sales managers are always notified'),

                        Toggle::make('deal_lost_enabled')
                            ->label('Deal Lost Notifications')
                            ->helperText('Notify when a deal is marked as lost'),

                        Select::make('deal_lost_roles')
                            ->label('Who receives Deal Lost notifications?')
                            ->multiple()
                            ->options([
                                'Sales' => 'Sales Team',
                                'Sales Manager' => 'Sales Managers',
                                'Admin' => 'Administrators',
                            ])
                            ->helperText('Deal owner and sales managers are always notified'),

                        Toggle::make('deal_assigned_enabled')
                            ->label('Deal Assignment Notifications')
                            ->helperText('Notify when a deal is reassigned to a different owner'),

                        Select::make('deal_assigned_roles')
                            ->label('Who receives Deal Assignment notifications?')
                            ->multiple()
                            ->options([
                                'Sales Manager' => 'Sales Managers',
                                'Admin' => 'Administrators',
                            ])
                            ->helperText('Old and new deal owners are always notified'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // TODO: Save notification settings to database or config
        // For now, we'll just show a success message

        $this->notify('success', 'Notification settings saved successfully!');
    }
}