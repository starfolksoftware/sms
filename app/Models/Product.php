<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'price',
        'sku',
        'stock_quantity',
        'product_type',
        'active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
            'product_type' => ProductType::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('data_ops')
            ->logOnly(['name', 'sku', 'price', 'active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Determine if this is a digital product (SaaS or info product)
     */
    public function isDigital(): bool
    {
        return $this->product_type->isDigital();
    }

    /**
     * Determine if this product requires inventory tracking
     */
    public function requiresInventory(): bool
    {
        return $this->product_type->requiresInventory();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
