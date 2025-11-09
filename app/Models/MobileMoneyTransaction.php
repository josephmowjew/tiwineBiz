<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileMoneyTransaction extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'shop_id',
        'provider',
        'transaction_id',
        'transaction_type',
        'msisdn',
        'sender_name',
        'receiver_name',
        'amount',
        'currency',
        'transaction_fee',
        'reference_type',
        'reference_id',
        'status',
        'request_payload',
        'response_payload',
        'webhook_received_at',
        'webhook_payload',
        'transaction_date',
        'confirmed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_fee' => 'decimal:2',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'webhook_received_at' => 'datetime',
            'webhook_payload' => 'array',
            'transaction_date' => 'datetime',
            'confirmed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
