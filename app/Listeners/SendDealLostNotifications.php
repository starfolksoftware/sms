<?php

namespace App\Listeners;

use App\Events\DealLost;
use App\Notifications\DealLostNotification;
use App\Services\DealNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealLostNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private DealNotificationService $notificationService
    ) {}

    public function handle(DealLost $event): void
    {
        $deal = $event->deal;

        // Get users to notify using the notification service
        $usersToNotify = $this->notificationService->getUsersForDealLost($deal);

        // Filter users based on their preferences
        $usersToNotify = $this->notificationService->filterUsersByPreferences(
            $usersToNotify, 
            'deal_lost'
        );

        // Send notifications
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealLostNotification($deal));
        }
    }
}
