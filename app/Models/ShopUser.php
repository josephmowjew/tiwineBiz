<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopUser extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'shop_id',
        'user_id',
        'role_id',
        'is_active',
        'invited_by',
        'invitation_token',
        'invitation_expires_at',
        'invitation_accepted_at',
        'joined_at',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'invitation_expires_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'joined_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
