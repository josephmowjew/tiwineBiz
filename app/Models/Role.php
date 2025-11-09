<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'shop_id',
        'name',
        'display_name',
        'description',
        'is_system_role',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'is_system_role' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function shopUsers(): HasMany
    {
        return $this->hasMany(ShopUser::class);
    }
}
