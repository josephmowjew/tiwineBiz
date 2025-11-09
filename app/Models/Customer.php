<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'customer_number',
        'name',
        'phone',
        'email',
        'whatsapp_number',
        'physical_address',
        'city',
        'district',
        'credit_limit',
        'current_balance',
        'total_spent',
        'total_credit_issued',
        'total_credit_collected',
        'trust_level',
        'payment_behavior_score',
        'purchase_count',
        'last_purchase_date',
        'average_purchase_value',
        'preferred_language',
        'preferred_contact_method',
        'notes',
        'tags',
        'is_active',
        'blocked_at',
        'block_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'total_spent' => 'decimal:2',
            'total_credit_issued' => 'decimal:2',
            'total_credit_collected' => 'decimal:2',
            'payment_behavior_score' => 'integer',
            'purchase_count' => 'integer',
            'last_purchase_date' => 'date',
            'average_purchase_value' => 'decimal:2',
            'tags' => 'array',
            'is_active' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
