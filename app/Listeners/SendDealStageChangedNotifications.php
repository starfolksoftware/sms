<?php

namespace App\Listeners;

use App\Events\DealStageChanged;
use App\Notifications\DealStageChangedNotification;
use App\Services\DealNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealStageChangedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private DealNotificationService $notificationService
    ) {}

    public function handle(DealStageChanged $event): void
    {
        $deal = $event->deal;

        // Get users to notify using the notification service
        $usersToNotify = $this->notificationService->getUsersForDealStageChanged($deal);

        // Send notifications - let the notification's via() method handle channel selection
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealStageChangedNotification($deal, $event->from, $event->to));
        }
    }
}