<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = [
        'shop_id',
        'customer_id',
        'credit_id',
        'sale_id',
        'payment_number',
        'amount',
        'currency',
        'exchange_rate',
        'amount_in_base_currency',
        'payment_method',
        'transaction_reference',
        'mobile_money_details',
        'bank_name',
        'cheque_number',
        'cheque_date',
        'payment_date',
        'cleared_at',
        'notes',
        'receipt_sent',
        'receipt_sent_at',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'amount_in_base_currency' => 'decimal:2',
            'mobile_money_details' => 'array',
            'cheque_date' => 'date',
            'payment_date' => 'datetime',
            'cleared_at' => 'datetime',
            'receipt_sent' => 'boolean',
            'receipt_sent_at' => 'datetime',
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

    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
