<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'shop_id',
        'name',
        'name_chichewa',
        'description',
        'sku',
        'barcode',
        'manufacturer_code',
        'category_id',
        'cost_price',
        'landing_cost',
        'selling_price',
        'base_currency',
        'base_currency_price',
        'last_exchange_rate_snapshot',
        'quantity',
        'unit',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'reorder_quantity',
        'storage_location',
        'shelf',
        'bin',
        'is_vat_applicable',
        'vat_rate',
        'tax_category',
        'primary_supplier_id',
        'attributes',
        'images',
        'track_batches',
        'track_serial_numbers',
        'has_expiry',
        'total_sold',
        'total_revenue',
        'last_sold_at',
        'last_restocked_at',
        'is_active',
        'discontinued_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'landing_cost' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'base_currency_price' => 'decimal:2',
            'last_exchange_rate_snapshot' => 'array',
            'quantity' => 'decimal:3',
            'min_stock_level' => 'decimal:3',
            'max_stock_level' => 'decimal:3',
            'reorder_point' => 'decimal:3',
            'reorder_quantity' => 'decimal:3',
            'is_vat_applicable' => 'boolean',
            'vat_rate' => 'decimal:2',
            'attributes' => 'array',
            'images' => 'array',
            'track_batches' => 'boolean',
            'track_serial_numbers' => 'boolean',
            'has_expiry' => 'boolean',
            'total_sold' => 'decimal:3',
            'total_revenue' => 'decimal:2',
            'last_sold_at' => 'datetime',
            'last_restocked_at' => 'datetime',
            'is_active' => 'boolean',
            'discontinued_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function primarySupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'primary_supplier_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function productBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get the total cost price (cost + landing cost)
     */
    public function getTotalCostPriceAttribute(): float
    {
        return (float) $this->cost_price + (float) $this->landing_cost;
    }

    /**
     * Calculate the minimum price to avoid loss
     * Formula: landing_cost (which is the total cost including purchase price + shipping + customs + MRA)
     */
    public function calculateMinimumPrice(): float
    {
        return (float) $this->landing_cost;
    }

    /**
     * Check if a given price is acceptable (not below minimum)
     */
    public function isPriceAcceptable(float $price): bool
    {
        $minimumPrice = $this->calculateMinimumPrice();
        return $price >= $minimumPrice;
    }

    /**
     * Check if a discount amount is acceptable
     *
     * @param float $discountAmount The discount amount to apply
     * @param int $quantity The quantity being sold
     * @return bool True if discount is acceptable, false otherwise
     */
    public function isDiscountAcceptable(float $discountAmount, int $quantity = 1): bool
    {
        $totalSellingPrice = $this->selling_price * $quantity;
        $discountedPrice = $totalSellingPrice - $discountAmount;

        // Calculate minimum acceptable total price
        $minimumPrice = $this->calculateMinimumPrice();
        $minimumTotal = $minimumPrice * $quantity;

        return $discountedPrice >= $minimumTotal;
    }

    /**
     * Get the maximum allowed discount for a given quantity
     *
     * @param int $quantity The quantity being sold
     * @return float Maximum discount amount allowed
     */
    public function getMaximumDiscount(int $quantity = 1): float
    {
        $totalSellingPrice = $this->selling_price * $quantity;
        $minimumPrice = $this->calculateMinimumPrice();
        $minimumTotal = $minimumPrice * $quantity;

        $maxDiscount = $totalSellingPrice - $minimumTotal;

        return max(0, $maxDiscount);
    }

    /**
     * Get discount safety information for UI guidance
     *
     * @return array{safeDiscount: float, warningDiscount: float, maximumDiscount: float}
     */
    public function getDiscountGuidance(int $quantity = 1): array
    {
        $totalSellingPrice = $this->selling_price * $quantity;

        // Safe discount: up to 5% off
        $safeDiscount = $totalSellingPrice * 0.05;

        // Warning discount: 5-15% off
        $warningDiscount = $totalSellingPrice * 0.15;

        // Maximum discount: calculated to not go below minimum price
        $maximumDiscount = $this->getMaximumDiscount($quantity);

        return [
            'safe_discount' => round($safeDiscount, 2),
            'warning_discount' => round($warningDiscount, 2),
            'maximum_discount' => round($maximumDiscount, 2),
            'minimum_price' => $this->calculateMinimumPrice(),
        ];
    }
}
