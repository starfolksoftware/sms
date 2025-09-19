<?php

namespace App\Listeners;

use App\Events\DealStageChanged;
use App\Models\User;
use App\Notifications\DealStageChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealStageChangedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DealStageChanged $event): void
    {
        $deal = $event->deal;

        // Get users to notify: deal owner and prior owner (if reassigned within 24h) and watchers
        $usersToNotify = collect();

        // Add deal owner if exists
        if ($deal->owner) {
            $usersToNotify->push($deal->owner);
        }

        // Add users with 'manage_deals' permission (sales managers) for stage changes
        $salesManagers = User::permission('manage_deals')->get();
        $usersToNotify = $usersToNotify->merge($salesManagers)->unique('id');

        // Send notifications
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealStageChangedNotification($deal, $event->from, $event->to));
        }
    }
}