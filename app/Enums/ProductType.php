<?php

namespace App\Enums;

enum ProductType: string
{
    case Saas = 'saas';
    case InfoProduct = 'info_product';
    case Physical = 'physical';
    case Digital = 'digital';

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
            self::Saas => 'SaaS',
            self::InfoProduct => 'Info Product',
            self::Physical => 'Physical',
            self::Digital => 'Digital',
        };
    }

    /**
     * Determine if this is a digital product type
     */
    public function isDigital(): bool
    {
        return in_array($this, [self::Saas, self::InfoProduct, self::Digital]);
    }

    /**
     * Determine if this product type requires inventory tracking
     */
    public function requiresInventory(): bool
    {
        return $this === self::Physical;
    }
}
