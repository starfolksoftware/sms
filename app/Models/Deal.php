<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Logging\LogsDeletions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    use HasFactory, SoftDeletes, LogsDeletions;

    protected $fillable = [
        'title','contact_id','product_id','owner_id',
        'amount','currency','stage','status','expected_close_date',
        'probability','lost_reason','won_amount','closed_at',
        'source','source_meta','notes',
    ];

    protected $casts = [
        'source_meta' => 'array',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
}
