<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBranch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'product_id',
        'branch_id',
        'quantity',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'cost_price',
        'landing_cost',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'min_stock_level' => 'decimal:3',
        'max_stock_level' => 'decimal:3',
        'reorder_point' => 'decimal:3',
        'cost_price' => 'decimal:2',
        'landing_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the shop that owns the product branch.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the product for this branch.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the branch for this product.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if stock is low.
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_stock_level;
    }

    /**
     * Check if stock is critically low.
     */
    public function isCriticallyLowStock(): bool
    {
        return $this->quantity <= ($this->min_stock_level * 0.5);
    }

    /**
     * Check if stock needs reorder.
     */
    public function needsReorder(): bool
    {
        return $this->quantity <= $this->reorder_point;
    }

    /**
     * Get stock status.
     */
    public function getStockStatus(): string
    {
        if ($this->isCriticallyLowStock()) {
            return 'critical';
        }

        if ($this->isLowStock() || $this->needsReorder()) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Scope to only include active records.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for a specific shop.
     */
    public function scopeForShop($query, string $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope for a specific branch.
     */
    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope for a specific product.
     */
    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'min_stock_level');
    }

    /**
     * Scope to get out of stock items.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }
}
