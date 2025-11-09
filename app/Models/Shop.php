<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'owner_id',
        'name',
        'business_type',
        'legal_name',
        'registration_number',
        'tpin',
        'vrn',
        'is_vat_registered',
        'phone',
        'email',
        'website',
        'address',
        'city',
        'district',
        'country',
        'latitude',
        'longitude',
        'logo_url',
        'primary_color',
        'default_currency',
        'fiscal_year_start_month',
        'subscription_tier',
        'subscription_status',
        'subscription_started_at',
        'subscription_expires_at',
        'trial_ends_at',
        'features',
        'limits',
        'settings',
        'is_active',
        'deactivated_at',
        'deactivation_reason',
    ];

    protected function casts(): array
    {
        return [
            'is_vat_registered' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'fiscal_year_start_month' => 'integer',
            'subscription_started_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'features' => 'array',
            'limits' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_users')
            ->using(ShopUser::class)
            ->withPivot('role_id', 'is_active', 'joined_at', 'last_accessed_at');
    }

    public function shopUsers(): HasMany
    {
        return $this->hasMany(ShopUser::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
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

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function mobileMoneyTransactions(): HasMany
    {
        return $this->hasMany(MobileMoneyTransaction::class);
    }

    public function efdTransactions(): HasMany
    {
        return $this->hasMany(EfdTransaction::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function syncQueue(): HasMany
    {
        return $this->hasMany(SyncQueue::class);
    }
}
