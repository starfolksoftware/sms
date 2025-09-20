<?php

namespace App\Listeners;

use App\Events\DealAssigned;
use App\Notifications\DealAssignedNotification;
use App\Services\DealNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealAssignedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private DealNotificationService $notificationService
    ) {}

    public function handle(DealAssigned $event): void
    {
        $deal = $event->deal;
        $oldOwner = $event->oldOwner;
        $newOwner = $event->newOwner;

        // Get users to notify using the notification service
        $usersToNotify = $this->notificationService->getUsersForDealAssigned($deal, $oldOwner, $newOwner);

        // Send notifications - let the notification's via() method handle channel selection
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealAssignedNotification($deal, $oldOwner, $newOwner));
        }
    }
}