<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookFailure extends Model
{
    use HasFactory;

    protected $fillable = [
        'direction',
        'event_type',
        'endpoint',
        'payload',
        'headers',
        'error_message',
        'stack_trace',
        'final_attempts',
        'first_failed_at',
        'final_failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'first_failed_at' => 'datetime',
        'final_failed_at' => 'datetime',
    ];

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeByReason(string $reason)
    {
        return $this->where('failure_reason', $reason);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('final_failed_at', '>=', now()->subDays($days));
    }
}
