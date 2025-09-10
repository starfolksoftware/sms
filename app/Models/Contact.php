<?php

namespace App\Models;

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
        'owner_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'source_meta' => 'array',
            'status' => ContactStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $contact) {
            // Auto-sync the name field from first_name and last_name
            if ($contact->isDirty(['first_name', 'last_name']) || empty($contact->name)) {
                $contact->name = trim(collect([$contact->first_name, $contact->last_name])->filter()->implode(' ')) ?: null;
            }

        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('data_ops')
            ->logOnly(['name', 'email', 'company', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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
