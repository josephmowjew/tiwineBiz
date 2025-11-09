<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'supplier_id',
        'po_number',
        'subtotal',
        'tax_amount',
        'freight_cost',
        'insurance_cost',
        'customs_duty',
        'clearing_fee',
        'transport_cost',
        'other_charges',
        'total_amount',
        'currency',
        'exchange_rate',
        'amount_in_base_currency',
        'status',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'shipping_method',
        'tracking_number',
        'border_point',
        'clearing_agent_name',
        'clearing_agent_phone',
        'customs_entry_number',
        'documents',
        'notes',
        'internal_notes',
        'created_by',
        'approved_by',
        'sent_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'freight_cost' => 'decimal:2',
            'insurance_cost' => 'decimal:2',
            'customs_duty' => 'decimal:2',
            'clearing_fee' => 'decimal:2',
            'transport_cost' => 'decimal:2',
            'other_charges' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'amount_in_base_currency' => 'decimal:2',
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'actual_delivery_date' => 'date',
            'documents' => 'array',
            'sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function productBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }
}
