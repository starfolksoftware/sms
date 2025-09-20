<?php

namespace App\Notifications\Concerns;

use App\Services\DealNotificationService;

trait HasUserPreferences
{
    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        $eventType = $this->getEventType();

        // Get the notification service
        $notificationService = app(DealNotificationService::class);

        // Check if user should receive email notifications
        if ($notificationService->shouldReceiveNotification($notifiable, $eventType, 'email')) {
            $channels[] = 'mail';
        }

        // Check if user should receive database notifications
        if ($notificationService->shouldReceiveNotification($notifiable, $eventType, 'database')) {
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the event type for this notification.
     * This should be implemented by each notification class.
     */
    abstract protected function getEventType(): string;
}
