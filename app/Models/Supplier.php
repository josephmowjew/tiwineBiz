<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'name',
        'legal_name',
        'supplier_code',
        'contact_person',
        'contact_phone',
        'contact_email',
        'phone',
        'email',
        'website',
        'physical_address',
        'city',
        'country',
        'payment_terms',
        'credit_days',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'tax_id',
        'total_orders',
        'total_order_value',
        'average_delivery_days',
        'reliability_score',
        'last_order_date',
        'is_active',
        'is_preferred',
        'notes',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'credit_days' => 'integer',
            'total_orders' => 'integer',
            'total_order_value' => 'decimal:2',
            'average_delivery_days' => 'integer',
            'reliability_score' => 'integer',
            'last_order_date' => 'date',
            'is_active' => 'boolean',
            'is_preferred' => 'boolean',
            'tags' => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'primary_supplier_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function productBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }
}
