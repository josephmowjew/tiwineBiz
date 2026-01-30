<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'email',
        'phone',
        'password_hash',
        'name',
        'profile_photo_url',
        'preferred_language',
        'timezone',
        'two_factor_secret',
        'two_factor_enabled',
        'failed_login_attempts',
        'locked_until',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'email_verified_at',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password_hash',
        'two_factor_secret',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'is_active' => 'boolean',
            'failed_login_attempts' => 'integer',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function ownedShops(): HasMany
    {
        return $this->hasMany(Shop::class, 'owner_id');
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_users')
            ->using(ShopUser::class)
            ->withPivot('role_id', 'is_active', 'joined_at', 'last_accessed_at');
    }

    public function shopUsers(): HasMany
    {
        return $this->hasMany(ShopUser::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function createdProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    public function updatedProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'updated_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'served_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function syncQueue(): HasMany
    {
        return $this->hasMany(SyncQueue::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')
            ->using(BranchUser::class)
            ->withPivot(
                'role_id',
                'is_active',
                'can_view_reports',
                'can_manage_stock',
                'can_process_sales',
                'can_manage_customers',
                'permissions',
                'assigned_at',
                'last_accessed_at'
            );
    }

    public function branchUsers(): HasMany
    {
        return $this->hasMany(BranchUser::class);
    }

    public function managedBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'manager_id');
    }

    public function getAccessibleBranchIds(?string $shopId = null): \Illuminate\Support\Collection
    {
        $query = Branch::query();

        // Get branches from shops the user owns
        $ownedShopIds = $this->ownedShops()->pluck('id');

        // Get branches from shops the user is a member of
        $memberShopIds = $this->shops()->pluck('shops.id');

        // Get branches the user is explicitly assigned to
        $explicitBranchIds = $this->branches()->where('branch_user.is_active', true)->pluck('branches.id');

        // Combine shop-level access (all branches in owned/member shops) + explicit branch access
        $allShopIds = $ownedShopIds->merge($memberShopIds)->unique();

        $query->where(function ($q) use ($allShopIds, $explicitBranchIds) {
            $q->whereIn('shop_id', $allShopIds)
                ->orWhereIn('id', $explicitBranchIds);
        });

        // Filter by specific shop if provided
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        return $query->where('is_active', true)->pluck('id');
    }

    /**
     * Get shifts for the user.
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Get active shift for the user.
     */
    public function activeShift(): ?Shift
    {
        return $this->shifts()->active()->first();
    }
}
