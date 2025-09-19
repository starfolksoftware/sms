<?php

namespace App\Listeners;

use App\Events\DealCreated;
use App\Models\User;
use App\Notifications\DealCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealCreatedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DealCreated $event): void
    {
        $deal = $event->deal;

        // Get users to notify: deal owner (if different from creator) and optional team lead
        $usersToNotify = collect();

        // Add deal owner if exists and is different from the creator
        if ($deal->owner && $deal->owner->id !== $deal->created_by) {
            $usersToNotify->push($deal->owner);
        }

        // Add users with 'manage_deals' permission (sales managers)
        $salesManagers = User::permission('manage_deals')->get();
        $usersToNotify = $usersToNotify->merge($salesManagers)->unique('id');

        // Send notifications
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealCreatedNotification($deal));
        }
    }
}