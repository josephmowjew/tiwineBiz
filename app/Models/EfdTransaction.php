<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfdTransaction extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'shop_id',
        'efd_device_id',
        'efd_device_serial',
        'sale_id',
        'fiscal_receipt_number',
        'fiscal_day_counter',
        'fiscal_signature',
        'qr_code_data',
        'verification_url',
        'total_amount',
        'vat_amount',
        'mra_response_code',
        'mra_response_message',
        'mra_acknowledgement',
        'transmitted_at',
        'transmission_status',
        'retry_count',
        'last_retry_at',
        'next_retry_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_day_counter' => 'integer',
            'total_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'mra_acknowledgement' => 'array',
            'transmitted_at' => 'datetime',
            'retry_count' => 'integer',
            'last_retry_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
