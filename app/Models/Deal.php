<?php

namespace App\Models;

use App\Events\DealLost;
use App\Events\DealWon;
use App\Logging\LogsDeletions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Deal extends Model
{
    use HasFactory, LogsActivity, LogsDeletions, SoftDeletes;

    protected $fillable = [
        'title', 'contact_id', 'product_id', 'owner_id',
        'amount', 'currency', 'stage', 'status', 'expected_close_date',
        'probability', 'lost_reason', 'won_amount', 'closed_at',
        'source', 'source_meta', 'notes',
    ];

    protected $casts = [
        'source_meta' => 'array',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
    ];

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function markAsWon(?float $wonAmount = null): void
    {
        $previousStatus = $this->status;

        $this->update([
            'status' => 'won',
            'won_amount' => $wonAmount ?? $this->amount,
            'closed_at' => now(),
        ]);

        activity('deal.won')
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties([
                'won_amount' => $this->won_amount,
                'currency' => $this->currency,
                'previous_status' => $previousStatus,
                'closed_at' => $this->closed_at,
            ])
            ->log('Deal marked as won');

        DealWon::dispatch($this, $this->won_amount);
    }

    public function markAsLost(string $reason): void
    {
        $previousStatus = $this->status;

        $this->update([
            'status' => 'lost',
            'lost_reason' => $reason,
            'closed_at' => now(),
        ]);

        activity('deal.lost')
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties([
                'lost_reason' => $reason,
                'previous_status' => $previousStatus,
                'closed_at' => $this->closed_at,
            ])
            ->log('Deal marked as lost');

        DealLost::dispatch($this, $reason);
    }
}
