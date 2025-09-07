<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

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
        ];
    }

    /**
     * Determine if this is a digital product (SaaS or info product)
     */
    public function isDigital(): bool
    {
        return in_array($this->product_type, ['saas', 'info_product', 'digital']);
    }

    /**
     * Determine if this product requires inventory tracking
     */
    public function requiresInventory(): bool
    {
        return $this->product_type === 'physical';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
