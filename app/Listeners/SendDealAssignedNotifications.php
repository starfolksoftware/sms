<?php

namespace App\Listeners;

use App\Events\DealAssigned;
use App\Notifications\DealAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendDealAssignedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DealAssigned $event): void
    {
        $deal = $event->deal;
        $oldOwner = $event->oldOwner;
        $newOwner = $event->newOwner;

        // Get users to notify: new owner and old owner
        $usersToNotify = collect();

        // Add new owner
        $usersToNotify->push($newOwner);

        // Add old owner if exists and is different from new owner
        if ($oldOwner && $oldOwner->id !== $newOwner->id) {
            $usersToNotify->push($oldOwner);
        }

        // Send notifications
        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DealAssignedNotification($deal, $oldOwner, $newOwner));
        }
    }
}