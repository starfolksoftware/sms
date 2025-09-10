<?php

namespace App\Enums;

enum ContactSource: string
{
    case WebsiteForm = 'website_form';
    case MetaAds = 'meta_ads';
    case X = 'x';
    case Instagram = 'instagram';
    case Referral = 'referral';
    case Manual = 'manual';
    case Other = 'other';

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
            self::WebsiteForm => 'Website Form',
            self::MetaAds => 'Meta Ads',
            self::X => 'X (Twitter)',
            self::Instagram => 'Instagram',
            self::Referral => 'Referral',
            self::Manual => 'Manual Entry',
            self::Other => 'Other',
        };
    }

    /**
     * Get the icon class for the source
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::WebsiteForm => 'fas fa-globe',
            self::MetaAds => 'fab fa-facebook',
            self::X => 'fab fa-twitter',
            self::Instagram => 'fab fa-instagram',
            self::Referral => 'fas fa-users',
            self::Manual => 'fas fa-user-plus',
            self::Other => 'fas fa-question',
        };
    }

    /**
     * Check if source is from social media
     */
    public function isSocialMedia(): bool
    {
        return in_array($this, [self::MetaAds, self::X, self::Instagram]);
    }

    /**
     * Check if source is digital/online
     */
    public function isDigital(): bool
    {
        return in_array($this, [self::WebsiteForm, self::MetaAds, self::X, self::Instagram]);
    }
}
