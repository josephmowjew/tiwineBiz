<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasUuids;

    protected $fillable = [
        'shop_id',
        'sale_number',
        'customer_id',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance',
        'change_given',
        'payment_status',
        'payment_methods',
        'currency',
        'exchange_rate',
        'amount_in_base_currency',
        'is_fiscalized',
        'efd_device_id',
        'efd_receipt_number',
        'efd_qr_code',
        'efd_fiscal_signature',
        'efd_transmitted_at',
        'efd_response',
        'sale_type',
        'notes',
        'internal_notes',
        'sale_date',
        'completed_at',
        'served_by',
        'shift_id',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'refunded_at',
        'refund_amount',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'change_given' => 'decimal:2',
            'payment_methods' => 'array',
            'exchange_rate' => 'decimal:4',
            'amount_in_base_currency' => 'decimal:2',
            'is_fiscalized' => 'boolean',
            'efd_transmitted_at' => 'datetime',
            'efd_response' => 'array',
            'sale_date' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'refund_amount' => 'decimal:2',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function credit(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function efdTransactions(): HasMany
    {
        return $this->hasMany(EfdTransaction::class);
    }

    public function mobileMoneyTransactions(): HasMany
    {
        return $this->hasMany(MobileMoneyTransaction::class, 'reference_id')
            ->where('reference_type', 'sale');
    }
}
