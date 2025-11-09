<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'base_currency',
        'target_currency',
        'official_rate',
        'street_rate',
        'rate_used',
        'effective_date',
        'valid_until',
        'source',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'official_rate' => 'decimal:4',
            'street_rate' => 'decimal:4',
            'effective_date' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
