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

        // Send notifications - let the notification's via() method handle channel selection
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealWonNotification($deal));
        }
    }
}
