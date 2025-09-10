<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Deal extends Model
{
    /** @use HasFactory<\Database\Factories\DealFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'value',
        'amount',
        'currency',
        'status',
        'stage',
        'expected_close_date',
        'probability',
        'lost_reason',
        'won_amount',
        'closed_at',
        'source',
        'source_meta',
        'notes',
        'contact_id',
        'product_id',
        'owner_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'amount' => 'decimal:2',
            'won_amount' => 'decimal:2',
            'expected_close_date' => 'date',
            'closed_at' => 'datetime',
            'source_meta' => 'array',
            'probability' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('data_ops')
            ->logOnly(['title', 'value', 'amount', 'status', 'stage'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine if the deal is closed (won or lost)
     */
    public function isClosed(): bool
    {
        return in_array($this->status, ['won', 'lost']);
    }

    /**
     * Determine if the deal is won
     */
    public function isWon(): bool
    {
        return $this->status === 'won';
    }

    /**
     * Determine if the deal is lost
     */
    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    /**
     * Get the deal's effective amount (won_amount if won, amount otherwise)
     */
    public function getEffectiveAmount(): ?float
    {
        if ($this->isWon() && $this->won_amount !== null) {
            return $this->won_amount;
        }

        return $this->amount ?? $this->value;
    }
}
