<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Deactivated = 'deactivated';
    case PendingInvite = 'pending_invite';

    /**
     * Get all possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the display name for the enum value
     */
    public function displayName(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Deactivated => 'Deactivated',
            self::PendingInvite => 'Pending Invite',
        };
    }

    /**
     * Get the badge class for styling
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-green-100 text-green-800',
            self::Deactivated => 'bg-red-100 text-red-800',
            self::PendingInvite => 'bg-yellow-100 text-yellow-800',
        };
    }

    /**
     * Check if user can login
     */
    public function canLogin(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if user is pending invitation
     */
    public function isPending(): bool
    {
        return $this === self::PendingInvite;
    }
}
