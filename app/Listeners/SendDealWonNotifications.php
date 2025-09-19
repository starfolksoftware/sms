<?php

namespace App\Listeners;

use App\Events\DealWon;
use App\Notifications\DealWonNotification;
use App\Services\DealNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealWonNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private DealNotificationService $notificationService
    ) {}

    public function handle(DealWon $event): void
    {
        $deal = $event->deal;

        // Get users to notify using the notification service
        $usersToNotify = $this->notificationService->getUsersForDealWon($deal);

        // Filter users based on their preferences
        $usersToNotify = $this->notificationService->filterUsersByPreferences(
            $usersToNotify, 
            'deal_won'
        );

        // Send notifications
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealWonNotification($deal));
        }
    }
}
