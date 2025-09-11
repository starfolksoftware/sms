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
        'contact_id',
        'product_id',
        'owner_id',
        'amount',
        'currency',
        'stage',
        'status',
        'expected_close_date',
        'probability',
        'lost_reason',
        'won_amount',
        'closed_at',
        'source',
        'source_meta',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'source_meta' => 'array',
            'expected_close_date' => 'date',
            'closed_at' => 'datetime',
            'amount' => 'decimal:2',
            'won_amount' => 'decimal:2',
            'probability' => 'integer',
        ];
    }

    /**
     * Backward-compatible accessor for legacy 'value' field.
     */
    public function getValueAttribute(): ?string
    {
        $amount = $this->getAttribute('amount');
        if ($amount === null) {
            return null;
        }
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * Backward-compatible mutator for legacy 'value' field -> maps to 'amount'.
     */
    public function setValueAttribute($value): void
    {
        $this->attributes['amount'] = $value;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('data_ops')
            // Include legacy 'value' alias for backward compatibility in logs
            ->logOnly(['title', 'amount', 'value', 'status', 'stage'])
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
}
