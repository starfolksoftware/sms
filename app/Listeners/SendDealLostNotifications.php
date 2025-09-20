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

        // Send notifications - let the notification's via() method handle channel selection
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealLostNotification($deal));
        }
    }
}
