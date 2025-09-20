<?php

namespace App\Filament\Pages;

use App\Models\UserNotificationPreference;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;

class UserNotificationPreferences extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'My Notification Preferences';

    protected static ?string $title = 'My Notification Preferences';

    protected static \UnitEnum|string|null $navigationGroup = 'Profile';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.user-notification-preferences';

    // Form data properties
    public bool $deal_created_email = false;

    public bool $deal_created_database = true;

    public bool $deal_stage_changed_email = false;

    public bool $deal_stage_changed_database = true;

    public bool $deal_won_email = false;

    public bool $deal_won_database = true;

    public bool $deal_lost_email = false;

    public bool $deal_lost_database = true;

    public bool $deal_assigned_email = true;

    public bool $deal_assigned_database = true;

    public function mount(): void
    {
        $this->loadUserPreferences();
    }

    protected function loadUserPreferences(): void
    {
        $user = Auth::user();

        $events = [
            'deal_created',
            'deal_stage_changed',
            'deal_won',
            'deal_lost',
            'deal_assigned',
        ];

        foreach ($events as $event) {
            $this->{$event.'_email'} = UserNotificationPreference::isUserPreferenceEnabled(
                $user->id,
                $event,
                'email'
            );
            $this->{$event.'_database'} = UserNotificationPreference::isUserPreferenceEnabled(
                $user->id,
                $event,
                'database'
            );
        }
    }

    public function save(): void
    {
        try {
            $user = Auth::user();

            $events = [
                'deal_created',
                'deal_stage_changed',
                'deal_won',
                'deal_lost',
                'deal_assigned',
            ];

            foreach ($events as $event) {
                UserNotificationPreference::setUserPreference(
                    $user->id,
                    $event,
                    'email',
                    $this->{$event.'_email'}
                );

                UserNotificationPreference::setUserPreference(
                    $user->id,
                    $event,
                    'database',
                    $this->{$event.'_database'}
                );
            }

            Notification::make()
                ->title('Preferences Updated')
                ->body('Your notification preferences have been successfully updated.')
                ->success()
                ->send();

        } catch (Halt $exception) {
            return;
        }
    }
}
