<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Support\Collection;

class DealNotificationService
{
    /**
     * Get users to notify for DealCreated event
     */
    public function getUsersForDealCreated(Deal $deal): Collection
    {
        $users = collect();

        // Add deal owner if exists and is different from the creator
        if ($deal->owner && $deal->owner->id !== auth()->id()) {
            $users->push($deal->owner);
        }

        // Add users with 'manage_deals' permission (sales managers)
        $salesManagers = User::permission('manage_deals')->get();
        $users = $users->merge($salesManagers);

        return $users->unique('id');
    }

    /**
     * Get users to notify for DealStageChanged event
     */
    public function getUsersForDealStageChanged(Deal $deal): Collection
    {
        $users = collect();

        // Add deal owner if exists
        if ($deal->owner) {
            $users->push($deal->owner);
        }

        // Add users with 'manage_deals' permission (sales managers)
        $salesManagers = User::permission('manage_deals')->get();
        $users = $users->merge($salesManagers);

        // TODO: Add prior owner if reassigned within 24h
        // TODO: Add watchers (future feature)

        return $users->unique('id');
    }

    /**
     * Get users to notify for DealWon event
     */
    public function getUsersForDealWon(Deal $deal): Collection
    {
        $users = collect();

        // Add deal owner if exists
        if ($deal->owner) {
            $users->push($deal->owner);
        }

        // Add users with 'manage_deals' permission (sales managers)
        $salesManagers = User::permission('manage_deals')->get();
        $users = $users->merge($salesManagers);

        // TODO: Add optional finance/reporting group

        return $users->unique('id');
    }

    /**
     * Get users to notify for DealLost event
     */
    public function getUsersForDealLost(Deal $deal): Collection
    {
        $users = collect();

        // Add deal owner if exists
        if ($deal->owner) {
            $users->push($deal->owner);
        }

        // Add users with 'manage_deals' permission (sales managers)
        $salesManagers = User::permission('manage_deals')->get();
        $users = $users->merge($salesManagers);

        // TODO: Add optional finance/reporting group

        return $users->unique('id');
    }

    /**
     * Get users to notify for DealAssigned event
     */
    public function getUsersForDealAssigned(Deal $deal, ?User $oldOwner, User $newOwner): Collection
    {
        $users = collect();

        // Add new owner
        $users->push($newOwner);

        // Add old owner if exists and is different from new owner
        if ($oldOwner && $oldOwner->id !== $newOwner->id) {
            $users->push($oldOwner);
        }

        return $users->unique('id');
    }

    /**
     * Check if user should receive notifications for a specific event type
     * This will be used later when we implement user preferences
     */
    public function shouldReceiveNotification(User $user, string $eventType, string $channel = 'mail'): bool
    {
        // TODO: Implement user preferences checking
        // For now, return true for all users
        return true;
    }

    /**
     * Filter users based on their notification preferences
     */
    public function filterUsersByPreferences(Collection $users, string $eventType, string $channel = 'mail'): Collection
    {
        return $users->filter(function (User $user) use ($eventType, $channel) {
            return $this->shouldReceiveNotification($user, $eventType, $channel);
        });
    }
}