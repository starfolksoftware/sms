<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NotificationSettings extends Settings
{
    // Deal Created Notifications
    public bool $deal_created_enabled;

    public array $deal_created_roles;

    public array $deal_created_users;

    public bool $deal_created_email_enabled;

    public bool $deal_created_database_enabled;

    // Deal Stage Changed Notifications
    public bool $deal_stage_changed_enabled;

    public array $deal_stage_changed_roles;

    public array $deal_stage_changed_users;

    public bool $deal_stage_changed_email_enabled;

    public bool $deal_stage_changed_database_enabled;

    // Deal Won Notifications
    public bool $deal_won_enabled;

    public array $deal_won_roles;

    public array $deal_won_users;

    public bool $deal_won_email_enabled;

    public bool $deal_won_database_enabled;

    // Deal Lost Notifications
    public bool $deal_lost_enabled;

    public array $deal_lost_roles;

    public array $deal_lost_users;

    public bool $deal_lost_email_enabled;

    public bool $deal_lost_database_enabled;

    // Deal Assigned Notifications
    public bool $deal_assigned_enabled;

    public array $deal_assigned_roles;

    public array $deal_assigned_users;

    public bool $deal_assigned_email_enabled;

    public bool $deal_assigned_database_enabled;

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
            'deal_created' => $this->deal_created_enabled,
            'deal_stage_changed' => $this->deal_stage_changed_enabled,
            'deal_won' => $this->deal_won_enabled,
            'deal_lost' => $this->deal_lost_enabled,
            'deal_assigned' => $this->deal_assigned_enabled,
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
            'deal_created_email' => $this->deal_created_email_enabled,
            'deal_created_database' => $this->deal_created_database_enabled,
            'deal_stage_changed_email' => $this->deal_stage_changed_email_enabled,
            'deal_stage_changed_database' => $this->deal_stage_changed_database_enabled,
            'deal_won_email' => $this->deal_won_email_enabled,
            'deal_won_database' => $this->deal_won_database_enabled,
            'deal_lost_email' => $this->deal_lost_email_enabled,
            'deal_lost_database' => $this->deal_lost_database_enabled,
            'deal_assigned_email' => $this->deal_assigned_email_enabled,
            'deal_assigned_database' => $this->deal_assigned_database_enabled,
            default => false,
        };
    }

    /**
     * Get roles that should receive notifications for an event
     */
    public function getNotificationRoles(string $eventType): array
    {
        return match ($eventType) {
            'deal_created' => $this->deal_created_roles,
            'deal_stage_changed' => $this->deal_stage_changed_roles,
            'deal_won' => $this->deal_won_roles,
            'deal_lost' => $this->deal_lost_roles,
            'deal_assigned' => $this->deal_assigned_roles,
            default => [],
        };
    }

    /**
     * Get specific users that should receive notifications for an event
     */
    public function getNotificationUsers(string $eventType): array
    {
        return match ($eventType) {
            'deal_created' => $this->deal_created_users,
            'deal_stage_changed' => $this->deal_stage_changed_users,
            'deal_won' => $this->deal_won_users,
            'deal_lost' => $this->deal_lost_users,
            'deal_assigned' => $this->deal_assigned_users,
            default => [],
        };
    }
}
