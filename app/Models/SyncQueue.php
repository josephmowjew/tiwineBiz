<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncQueue extends Model
{
    use HasUuids;

    protected $fillable = [
        'shop_id',
        'user_id',
        'entity_type',
        'entity_id',
        'action',
        'data',
        'client_timestamp',
        'device_id',
        'status',
        'attempts',
        'last_attempt_at',
        'error_message',
        'priority',
        'conflict_data',
        'resolved_by',
        'resolved_at',
        'resolution',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'client_timestamp' => 'datetime',
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
            'priority' => 'integer',
            'conflict_data' => 'array',
            'resolved_at' => 'datetime',
            'processed_at' => 'datetime',
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

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
