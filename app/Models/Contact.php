<?php

namespace App\Models;

use App\Enums\ContactSource;
use App\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'company',
        'job_title',
        'status',
        'source',
        'source_meta',
        'notes',
        'owner_id',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
            'source' => ContactSource::class,
            'source_meta' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('data_ops')
            ->logOnly(['first_name', 'last_name', 'name', 'email', 'company', 'status', 'source'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the full name attribute
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }

        return $this->name ?? '';
    }

    /**
     * Set the name field when first_name or last_name is updated
     */
    protected static function booted(): void
    {
        static::creating(function (Contact $contact) {
            if ($contact->first_name && $contact->last_name && ! $contact->name) {
                $contact->name = "{$contact->first_name} {$contact->last_name}";
            }
        });

        static::updating(function (Contact $contact) {
            // Only auto-set name if first_name and last_name are provided AND name is not explicitly set
            if ($contact->isDirty(['first_name', 'last_name']) && ! $contact->isDirty('name')) {
                if ($contact->first_name && $contact->last_name) {
                    $contact->name = "{$contact->first_name} {$contact->last_name}";
                }
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
