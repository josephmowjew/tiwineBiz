<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'user_name',
        'shop_id',
        'branch_id',
        'start_time',
        'end_time',
        'opening_balance',
        'current_balance',
        'closing_balance',
        'expected_balance',
        'discrepancy',
        'transaction_count',
        'sales_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'discrepancy' => 'decimal:2',
        'sales_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the shift.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shop for this shift.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the branch for this shift.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get sales for this shift.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Check if shift is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && is_null($this->end_time);
    }

    /**
     * Calculate duration in seconds.
     */
    public function getDurationInSeconds(): int
    {
        if (! $this->start_time) {
            return 0;
        }

        $endTime = $this->end_time ?? now();

        return $endTime->diffInSeconds($this->start_time);
    }

    /**
     * Get formatted duration (HH:MM:SS).
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->getDurationInSeconds();
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Scope: Active shifts only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereNull('end_time');
    }

    /**
     * Scope: For specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: For specific shop.
     */
    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope: Date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }
}
