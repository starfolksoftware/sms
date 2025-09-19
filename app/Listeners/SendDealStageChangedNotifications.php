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

        // Filter users based on their preferences
        $usersToNotify = $this->notificationService->filterUsersByPreferences(
            $usersToNotify, 
            'deal_stage_changed'
        );

        // Send notifications
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealStageChangedNotification($deal, $event->from, $event->to));
        }
    }
}