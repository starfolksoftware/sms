<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Settings\NotificationSettings;
use Illuminate\Support\Collection;

class DealNotificationService
{
    public function __construct(
        private NotificationSettings $notificationSettings
    ) {}

    /**
     * Get users to notify for DealCreated event
     */
    public function getUsersForDealCreated(Deal $deal): Collection
    {
        return $this->getUsersForEvent('deal_created', $deal);
    }

    /**
     * Get users to notify for DealStageChanged event
     */
    public function getUsersForDealStageChanged(Deal $deal): Collection
    {
        return $this->getUsersForEvent('deal_stage_changed', $deal);
    }

    /**
     * Get users to notify for DealWon event
     */
    public function getUsersForDealWon(Deal $deal): Collection
    {
        return $this->getUsersForEvent('deal_won', $deal);
    }

    /**
     * Get users to notify for DealLost event
     */
    public function getUsersForDealLost(Deal $deal): Collection
    {
        return $this->getUsersForEvent('deal_lost', $deal);
    }

    /**
     * Get users to notify for DealAssigned event
     */
    public function getUsersForDealAssigned(Deal $deal, ?User $oldOwner, User $newOwner): Collection
    {
        $users = collect();

        // For deal assignments, always include the new owner and old owner
        $users->push($newOwner);
        if ($oldOwner && $oldOwner->id !== $newOwner->id) {
            $users->push($oldOwner);
        }

        // Add users from admin settings
        $adminUsers = $this->getUsersForEvent('deal_assigned', $deal);
        $users = $users->merge($adminUsers);

        return $users->unique('id');
    }

    /**
     * Get users to notify for a specific event based on admin settings
     */
    private function getUsersForEvent(string $eventType, Deal $deal): Collection
    {
        $users = collect();

        // Check if the event is enabled globally
        if (! $this->notificationSettings->isEventEnabled($eventType)) {
            return $users;
        }

        // Add deal owner if exists (except for deal creation where creator is owner)
        if ($deal->owner && $eventType !== 'deal_created') {
            $users->push($deal->owner);
        }

        // Add users by roles from admin settings
        $roles = $this->notificationSettings->getNotificationRoles($eventType);
        if (! empty($roles)) {
            $roleUsers = User::whereHas('roles', function ($query) use ($roles) {
                $query->whereIn('name', $roles);
            })->get();
            $users = $users->merge($roleUsers);
        }

        // Add specific users from admin settings
        $specificUserIds = $this->notificationSettings->getNotificationUsers($eventType);
        if (! empty($specificUserIds)) {
            $specificUsers = User::whereIn('id', $specificUserIds)->get();
            $users = $users->merge($specificUsers);
        }

        return $users->unique('id');
    }

    /**
     * Filter users based on their notification preferences and admin channel settings
     */
    public function filterUsersByPreferences(Collection $users, string $eventType, string $channel = 'mail'): Collection
    {
        // Convert 'mail' to 'email' for consistency
        $channelKey = $channel === 'mail' ? 'email' : $channel;

        // Check if the channel is enabled globally for this event
        if (! $this->notificationSettings->isChannelEnabled($eventType, $channelKey)) {
            return collect(); // Return empty collection if channel is disabled globally
        }

        return $users->filter(function (User $user) use ($eventType, $channelKey) {
            return $this->shouldReceiveNotification($user, $eventType, $channelKey);
        });
    }

    /**
     * Check if user should receive notifications for a specific event type and channel
     */
    public function shouldReceiveNotification(User $user, string $eventType, string $channel = 'email'): bool
    {
        // First check if the event and channel are enabled globally
        if (! $this->notificationSettings->isEventEnabled($eventType)) {
            return false;
        }

        if (! $this->notificationSettings->isChannelEnabled($eventType, $channel)) {
            return false;
        }

        // Then check user's personal preferences
        return UserNotificationPreference::isUserPreferenceEnabled($user->id, $eventType, $channel);
    }
}
