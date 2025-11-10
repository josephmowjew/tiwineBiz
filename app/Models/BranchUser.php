<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchUser extends Pivot
{
    use HasFactory, HasUuids;

    protected $table = 'branch_user';

    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'user_id',
        'role_id',
        'is_active',
        'can_view_reports',
        'can_manage_stock',
        'can_process_sales',
        'can_manage_customers',
        'permissions',
        'assigned_by',
        'assigned_at',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'can_view_reports' => 'boolean',
            'can_manage_stock' => 'boolean',
            'can_process_sales' => 'boolean',
            'can_manage_customers' => 'boolean',
            'permissions' => 'array',
            'assigned_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
