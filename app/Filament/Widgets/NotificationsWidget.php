<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NotificationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.notifications-widget';

    protected int | string | array $columnSpan = 'full';

    public function getNotifications()
    {
        return Auth::user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => class_basename($notification->type),
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                ];
            });
    }

    public function markAsRead(string $notificationId): void
    {
        Auth::user()
            ->notifications()
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);

        $this->dispatch('notification-read');
    }

    public function markAllAsRead(): void
    {
        Auth::user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        $this->dispatch('all-notifications-read');
    }
}