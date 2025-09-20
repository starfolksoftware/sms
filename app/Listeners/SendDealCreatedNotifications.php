<?php

namespace App\Listeners;

use App\Events\DealCreated;
use App\Notifications\DealCreatedNotification;
use App\Services\DealNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealCreatedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private DealNotificationService $notificationService
    ) {}

    public function handle(DealCreated $event): void
    {
        $deal = $event->deal;

        // Get users to notify using the notification service
        $usersToNotify = $this->notificationService->getUsersForDealCreated($deal);

        // Send notifications - let the notification's via() method handle channel selection
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealCreatedNotification($deal));
        }
    }
}