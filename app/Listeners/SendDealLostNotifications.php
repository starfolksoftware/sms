<?php

namespace App\Listeners;

use App\Events\DealLost;
use App\Models\User;
use App\Notifications\DealLostNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealLostNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DealLost $event): void
    {
        $deal = $event->deal;

        // Get users to notify: deal owner and users with sales manager role
        $usersToNotify = collect();

        // Add deal owner if exists
        if ($deal->owner) {
            $usersToNotify->push($deal->owner);
        }

        // Add users with 'manage_deals' permission (sales managers)
        $salesManagers = User::permission('manage_deals')->get();
        $usersToNotify = $usersToNotify->merge($salesManagers)->unique('id');

        // Send notifications
        Notification::send($usersToNotify, new DealLostNotification($deal));
    }
}
