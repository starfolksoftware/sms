<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Logging\LogsDeletions;

class Product extends Model
{
    use HasFactory, SoftDeletes, LogsDeletions;

    protected $fillable = [
        'name', 'type', 'status'
    ];
}
