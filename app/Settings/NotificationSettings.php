<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    // Deal Created Notifications
    public bool $dealCreatedEnabled;

    public array $dealCreatedRoles;

    public array $dealCreatedUsers;

    public bool $dealCreatedEmailEnabled;

    public bool $dealCreatedDatabaseEnabled;

    // Deal Stage Changed Notifications
    public bool $dealStageChangedEnabled;

    public array $dealStageChangedRoles;

    public array $dealStageChangedUsers;

    public bool $dealStageChangedEmailEnabled;

    public bool $dealStageChangedDatabaseEnabled;

    // Deal Won Notifications
    public bool $dealWonEnabled;

    public array $dealWonRoles;

    public array $dealWonUsers;

    public bool $dealWonEmailEnabled;

    public bool $dealWonDatabaseEnabled;

    // Deal Lost Notifications
    public bool $dealLostEnabled;

    public array $dealLostRoles;

    public array $dealLostUsers;

    public bool $dealLostEmailEnabled;

    public bool $dealLostDatabaseEnabled;

    // Deal Assigned Notifications
    public bool $dealAssignedEnabled;

    public array $dealAssignedRoles;

    public array $dealAssignedUsers;

    public bool $dealAssignedEmailEnabled;

    public bool $dealAssignedDatabaseEnabled;

    public static function group(): string
    {
        return 'notifications';
    }

    public static function casts(): array
    {
        return [];
    }

    /**
     * Check if a specific notification event is enabled
     */
    public function isEventEnabled(string $eventType): bool
    {
        return match ($eventType) {
            'deal_created' => $this->dealCreatedEnabled,
            'deal_stage_changed' => $this->dealStageChangedEnabled,
            'deal_won' => $this->dealWonEnabled,
            'deal_lost' => $this->dealLostEnabled,
            'deal_assigned' => $this->dealAssignedEnabled,
            default => false,
        };
    }

    /**
     * Check if a specific channel is enabled for an event
     */
    public function isChannelEnabled(string $eventType, string $channel): bool
    {
        if (! $this->isEventEnabled($eventType)) {
            return false;
        }

        return match ("{$eventType}_{$channel}") {
            'deal_created_email' => $this->dealCreatedEmailEnabled,
            'deal_created_database' => $this->dealCreatedDatabaseEnabled,
            'deal_stage_changed_email' => $this->dealStageChangedEmailEnabled,
            'deal_stage_changed_database' => $this->dealStageChangedDatabaseEnabled,
            'deal_won_email' => $this->dealWonEmailEnabled,
            'deal_won_database' => $this->dealWonDatabaseEnabled,
            'deal_lost_email' => $this->dealLostEmailEnabled,
            'deal_lost_database' => $this->dealLostDatabaseEnabled,
            'deal_assigned_email' => $this->dealAssignedEmailEnabled,
            'deal_assigned_database' => $this->dealAssignedDatabaseEnabled,
            default => false,
        };
    }

    /**
     * Get roles that should receive notifications for an event
     */
    public function getNotificationRoles(string $eventType): array
    {
        return match ($eventType) {
            'deal_created' => $this->dealCreatedRoles,
            'deal_stage_changed' => $this->dealStageChangedRoles,
            'deal_won' => $this->dealWonRoles,
            'deal_lost' => $this->dealLostRoles,
            'deal_assigned' => $this->dealAssignedRoles,
            default => [],
        };
    }

    /**
     * Get specific users that should receive notifications for an event
     */
    public function getNotificationUsers(string $eventType): array
    {
        return match ($eventType) {
            'deal_created' => $this->dealCreatedUsers,
            'deal_stage_changed' => $this->dealStageChangedUsers,
            'deal_won' => $this->dealWonUsers,
            'deal_lost' => $this->dealLostUsers,
            'deal_assigned' => $this->dealAssignedUsers,
            default => [],
        };
    }
}
