<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Branch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'name',
        'code',
        'branch_type',
        'phone',
        'email',
        'address',
        'city',
        'district',
        'latitude',
        'longitude',
        'manager_id',
        'is_active',
        'opened_at',
        'closed_at',
        'settings',
        'features',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'settings' => 'array',
            'features' => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')
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

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function productBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, Shop::class, 'id', 'shop_id', 'shop_id', 'id');
    }
}
