<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credit extends Model
{
    use HasUuids;

    protected $fillable = [
        'shop_id',
        'customer_id',
        'sale_id',
        'credit_number',
        'original_amount',
        'amount_paid',
        'balance',
        'issue_date',
        'due_date',
        'payment_term',
        'status',
        'last_reminder_sent_at',
        'reminder_count',
        'next_reminder_date',
        'collection_attempts',
        'last_collection_attempt_at',
        'escalation_level',
        'notes',
        'internal_notes',
        'created_by',
        'paid_at',
        'written_off_at',
        'written_off_by',
        'write_off_reason',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
            'last_reminder_sent_at' => 'datetime',
            'reminder_count' => 'integer',
            'next_reminder_date' => 'date',
            'collection_attempts' => 'integer',
            'last_collection_attempt_at' => 'datetime',
            'escalation_level' => 'integer',
            'paid_at' => 'datetime',
            'written_off_at' => 'datetime',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function writtenOffBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'written_off_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
