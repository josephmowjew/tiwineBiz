<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'purchase_order_id',
        'supplier_id',
        'batch_number',
        'lot_number',
        'initial_quantity',
        'remaining_quantity',
        'unit_cost',
        'currency',
        'product_cost',
        'freight_cost',
        'customs_duty',
        'clearing_fee',
        'other_costs',
        'total_landed_cost',
        'purchase_date',
        'manufacture_date',
        'expiry_date',
        'is_depleted',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'initial_quantity' => 'decimal:3',
            'remaining_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'product_cost' => 'decimal:2',
            'freight_cost' => 'decimal:2',
            'customs_duty' => 'decimal:2',
            'clearing_fee' => 'decimal:2',
            'other_costs' => 'decimal:2',
            'total_landed_cost' => 'decimal:2',
            'purchase_date' => 'date',
            'manufacture_date' => 'date',
            'expiry_date' => 'date',
            'is_depleted' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'batch_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }
}
