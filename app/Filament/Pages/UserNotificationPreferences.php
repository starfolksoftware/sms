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

    public static ?int $navigationSort = 2;

    public ?array $data = [];

    public function getView(): string
    {
        return 'filament.pages.user-notification-preferences';
    }

    public function mount(): void
    {
        $this->data = $this->getDefaultData();
    }

    protected function getDefaultData(): array
    {
        $user = Auth::user();
        $data = [];

        $events = [
            'deal_created',
            'deal_stage_changed',
            'deal_won',
            'deal_lost',
            'deal_assigned',
        ];

        foreach ($events as $event) {
            $data[$event.'_email'] = UserNotificationPreference::isUserPreferenceEnabled(
                $user->id,
                $event,
                'email'
            );
            $data[$event.'_database'] = UserNotificationPreference::isUserPreferenceEnabled(
                $user->id,
                $event,
                'database'
            );
        }

        return $data;
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
                    $this->data[$event.'_email'] ?? false
                );

                UserNotificationPreference::setUserPreference(
                    $user->id,
                    $event,
                    'database',
                    $this->data[$event.'_database'] ?? false
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
