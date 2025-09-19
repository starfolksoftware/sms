<?php

namespace App\Listeners;

use App\Events\DealWon;
use App\Models\User;
use App\Notifications\DealWonNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealWonNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DealWon $event): void
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
        Notification::send($usersToNotify, new DealWonNotification($deal));
    }
}
