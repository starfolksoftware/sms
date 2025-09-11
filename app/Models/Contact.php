<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Logging\LogsDeletions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory, SoftDeletes, LogsDeletions;

    protected $fillable = [
        'first_name','last_name','name','email','phone','company','job_title',
        'status','source','source_meta','owner_id','notes'
    ];

    protected $casts = [
        'source_meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $c): void {
            if (! $c->name) {
                $name = trim(collect([$c->first_name, $c->last_name])->filter()->implode(' '));
                $c->name = $name !== '' ? $name : $c->name;
            }
        });
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
