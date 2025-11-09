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
        'selling_price',
        'min_price',
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
            'selling_price' => 'decimal:2',
            'min_price' => 'decimal:2',
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
}
