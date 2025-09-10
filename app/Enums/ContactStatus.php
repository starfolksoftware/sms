<?php

namespace App\Enums;

enum ContactStatus: string
{
    case Lead = 'lead';
    case Qualified = 'qualified';
    case Customer = 'customer';
    case Archived = 'archived';

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
            self::Lead => 'Lead',
            self::Qualified => 'Qualified',
            self::Customer => 'Customer',
            self::Archived => 'Archived',
        };
    }

    /**
     * Get the badge class for styling
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Lead => 'bg-blue-100 text-blue-800',
            self::Qualified => 'bg-yellow-100 text-yellow-800',
            self::Customer => 'bg-green-100 text-green-800',
            self::Archived => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if contact is a potential customer
     */
    public function isPotential(): bool
    {
        return in_array($this, [self::Lead, self::Qualified]);
    }

    /**
     * Check if contact is active (not archived)
     */
    public function isActive(): bool
    {
        return $this !== self::Archived;
    }
}
