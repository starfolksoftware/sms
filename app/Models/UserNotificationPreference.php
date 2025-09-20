<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'email_enabled',
        'database_enabled',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'database_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has enabled a specific channel for an event type
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match ($channel) {
            'email' => $this->email_enabled,
            'database' => $this->database_enabled,
            default => false,
        };
    }

    /**
     * Get user preference for a specific event and channel
     */
    public static function isUserPreferenceEnabled(int $userId, string $eventType, string $channel): bool
    {
        $preference = static::where('user_id', $userId)
            ->where('event_type', $eventType)
            ->first();

        if (! $preference) {
            // Default preferences: database is always enabled, email is optional
            return $channel === 'database';
        }

        return $preference->isChannelEnabled($channel);
    }

    /**
     * Set user preference for a specific event and channel
     */
    public static function setUserPreference(int $userId, string $eventType, string $channel, bool $enabled): void
    {
        $preference = static::firstOrCreate(
            [
                'user_id' => $userId,
                'event_type' => $eventType,
            ],
            [
                'email_enabled' => $channel === 'email', // Default based on channel
                'database_enabled' => true, // Database notifications always enabled by default
            ]
        );

        if ($channel === 'email') {
            $preference->email_enabled = $enabled;
        } elseif ($channel === 'database') {
            $preference->database_enabled = $enabled;
        }

        $preference->save();
    }
}
