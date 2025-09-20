<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'direction',
        'endpoint',
        'event_type',
        'method',
        'headers_hash',
        'response_status',
        'response_body',
        'status',
        'attempts',
        'last_error',
        'sent_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markAsSuccess(): void
    {
        $this->update([
            'status' => 'success',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
            'completed_at' => now(),
        ]);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
