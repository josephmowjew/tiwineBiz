<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SyncQueue;
use App\Traits\HasBranchScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncService
{
    use HasBranchScope;

    /**
     * Supported entity types for sync.
     */
    protected array $supportedEntities = [
        'sale',
        'product',
        'customer',
        'payment',
        'credit',
    ];

    /**
     * Push changes from client to server.
     */
    public function push(array $changes, string $deviceId, string $userId): array
    {
        $results = [
            'success' => [],
            'conflicts' => [],
            'errors' => [],
            'summary' => [
                'total' => count($changes),
                'processed' => 0,
                'conflicts' => 0,
                'errors' => 0,
            ],
        ];

        DB::beginTransaction();

        try {
            foreach ($changes as $change) {
                $result = $this->processChange($change, $deviceId, $userId);

                if ($result['status'] === 'success') {
                    $results['success'][] = $result;
                    $results['summary']['processed']++;
                } elseif ($result['status'] === 'conflict') {
                    $results['conflicts'][] = $result;
                    $results['summary']['conflicts']++;
                } else {
                    $results['errors'][] = $result;
                    $results['summary']['errors']++;
                }
            }

            DB::commit();

            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sync push failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'device_id' => $deviceId,
            ]);

            throw $e;
        }
    }

    /**
     * Pull changes from server to client.
     */
    public function pull(string $lastSyncTimestamp, ?array $entityTypes = null): array
    {
        $since = Carbon::parse($lastSyncTimestamp);
        $accessibleBranchIds = $this->getAccessibleBranchIds();

        if ($accessibleBranchIds->isEmpty()) {
            return [
                'data' => [],
                'timestamp' => now()->toIso8601String(),
                'has_more' => false,
            ];
        }

        $entitiesToSync = $entityTypes ?? $this->supportedEntities;
        $changes = [];

        foreach ($entitiesToSync as $entityType) {
            $entityChanges = $this->getEntityChanges($entityType, $since, $accessibleBranchIds);
            if ($entityChanges->isNotEmpty()) {
                $changes[$entityType] = $entityChanges;
            }
        }

        return [
            'data' => $changes,
            'timestamp' => now()->toIso8601String(),
            'has_more' => false,
        ];
    }

    /**
     * Process a single change from client.
     */
    protected function processChange(array $change, string $deviceId, string $userId): array
    {
        try {
            $validated = $this->validateChange($change);

            if (! $validated['valid']) {
                return [
                    'status' => 'error',
                    'entity_type' => $change['entity_type'] ?? 'unknown',
                    'entity_id' => $change['entity_id'] ?? null,
                    'message' => $validated['message'],
                ];
            }

            // Queue the change
            $queueItem = $this->queueChange($change, $deviceId, $userId);

            // Attempt to process immediately
            $processed = $this->processQueueItem($queueItem);

            return $processed;
        } catch (\Exception $e) {
            Log::error('Failed to process change', [
                'change' => $change,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'entity_type' => $change['entity_type'] ?? 'unknown',
                'entity_id' => $change['entity_id'] ?? null,
                'message' => 'Processing failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Queue a change for processing.
     */
    protected function queueChange(array $change, string $deviceId, string $userId): SyncQueue
    {
        return SyncQueue::create([
            'shop_id' => auth()->user()->shops()->first()->id ?? $change['shop_id'],
            'user_id' => $userId,
            'entity_type' => $change['entity_type'],
            'entity_id' => $change['entity_id'],
            'action' => $change['action'],
            'data' => $change['data'],
            'client_timestamp' => $change['timestamp'] ?? now(),
            'device_id' => $deviceId,
            'status' => 'pending',
            'priority' => $change['priority'] ?? 5,
        ]);
    }

    /**
     * Process a queued sync item.
     */
    protected function processQueueItem(SyncQueue $queueItem): array
    {
        $queueItem->update([
            'status' => 'processing',
            'attempts' => $queueItem->attempts + 1,
            'last_attempt_at' => now(),
        ]);

        try {
            // Check for conflicts
            $conflict = $this->detectConflict($queueItem);

            if ($conflict) {
                return $this->handleConflict($queueItem, $conflict);
            }

            // Apply the change
            $result = $this->applyChange($queueItem);

            $queueItem->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            return [
                'status' => 'success',
                'entity_type' => $queueItem->entity_type,
                'entity_id' => $queueItem->entity_id,
                'action' => $queueItem->action,
                'server_timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            $queueItem->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Detect conflicts between client and server data.
     */
    protected function detectConflict(SyncQueue $queueItem): ?array
    {
        $model = $this->getModelForEntity($queueItem->entity_type);

        if (! $model) {
            return null;
        }

        $serverEntity = $model::find($queueItem->entity_id);

        if (! $serverEntity) {
            return null;
        }

        // Check if server version is newer than client version
        if ($serverEntity->updated_at->gt($queueItem->client_timestamp)) {
            return [
                'server_data' => $serverEntity->toArray(),
                'server_timestamp' => $serverEntity->updated_at->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * Handle sync conflict.
     */
    protected function handleConflict(SyncQueue $queueItem, array $conflict): array
    {
        $queueItem->update([
            'status' => 'conflict',
            'conflict_data' => $conflict['server_data'],
        ]);

        return [
            'status' => 'conflict',
            'entity_type' => $queueItem->entity_type,
            'entity_id' => $queueItem->entity_id,
            'client_data' => $queueItem->data,
            'server_data' => $conflict['server_data'],
            'client_timestamp' => $queueItem->client_timestamp->toIso8601String(),
            'server_timestamp' => $conflict['server_timestamp'],
            'message' => 'Conflict detected. Manual resolution required.',
        ];
    }

    /**
     * Apply a change to the database.
     */
    protected function applyChange(SyncQueue $queueItem): bool
    {
        $model = $this->getModelForEntity($queueItem->entity_type);

        if (! $model) {
            throw new \Exception("Unsupported entity type: {$queueItem->entity_type}");
        }

        switch ($queueItem->action) {
            case 'create':
                $model::create($queueItem->data);
                break;

            case 'update':
                $entity = $model::find($queueItem->entity_id);
                if ($entity) {
                    $entity->update($queueItem->data);
                }
                break;

            case 'delete':
                $entity = $model::find($queueItem->entity_id);
                if ($entity) {
                    $entity->delete();
                }
                break;

            default:
                throw new \Exception("Unsupported action: {$queueItem->action}");
        }

        return true;
    }

    /**
     * Get model class for entity type.
     */
    protected function getModelForEntity(string $entityType): ?string
    {
        $models = [
            'sale' => Sale::class,
            'product' => Product::class,
            'customer' => Customer::class,
            // Add more entity mappings as needed
        ];

        return $models[$entityType] ?? null;
    }

    /**
     * Get changes for an entity type since a timestamp.
     */
    protected function getEntityChanges(string $entityType, Carbon $since, Collection $branchIds): Collection
    {
        $model = $this->getModelForEntity($entityType);

        if (! $model) {
            return collect();
        }

        return $model::query()
            ->whereIn('branch_id', $branchIds)
            ->where('updated_at', '>', $since)
            ->orderBy('updated_at', 'asc')
            ->limit(100) // Pagination limit
            ->get()
            ->map(function ($entity) use ($entityType) {
                return [
                    'entity_type' => $entityType,
                    'entity_id' => $entity->id,
                    'action' => 'update',
                    'data' => $entity->toArray(),
                    'timestamp' => $entity->updated_at->toIso8601String(),
                ];
            });
    }

    /**
     * Validate a change request.
     */
    protected function validateChange(array $change): array
    {
        $required = ['entity_type', 'entity_id', 'action', 'data'];

        foreach ($required as $field) {
            if (! isset($change[$field])) {
                return [
                    'valid' => false,
                    'message' => "Missing required field: {$field}",
                ];
            }
        }

        if (! in_array($change['entity_type'], $this->supportedEntities)) {
            return [
                'valid' => false,
                'message' => "Unsupported entity type: {$change['entity_type']}",
            ];
        }

        if (! in_array($change['action'], ['create', 'update', 'delete'])) {
            return [
                'valid' => false,
                'message' => "Unsupported action: {$change['action']}",
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get sync status for a shop.
     */
    public function getSyncStatus(string $shopId): array
    {
        $pending = SyncQueue::where('shop_id', $shopId)
            ->where('status', 'pending')
            ->count();

        $conflicts = SyncQueue::where('shop_id', $shopId)
            ->where('status', 'conflict')
            ->count();

        $failed = SyncQueue::where('shop_id', $shopId)
            ->where('status', 'failed')
            ->count();

        $lastSync = SyncQueue::where('shop_id', $shopId)
            ->where('status', 'completed')
            ->latest('processed_at')
            ->first();

        return [
            'pending' => $pending,
            'conflicts' => $conflicts,
            'failed' => $failed,
            'last_sync_at' => $lastSync?->processed_at?->toIso8601String(),
            'has_issues' => $conflicts > 0 || $failed > 0,
        ];
    }

    /**
     * Resolve a sync conflict.
     */
    public function resolveConflict(string $queueItemId, string $resolution, ?array $mergedData = null): array
    {
        $queueItem = SyncQueue::find($queueItemId);

        if (! $queueItem || $queueItem->status !== 'conflict') {
            throw new \Exception('Invalid queue item or not in conflict state');
        }

        DB::beginTransaction();

        try {
            switch ($resolution) {
                case 'client_wins':
                    $queueItem->update(['status' => 'pending']);
                    $result = $this->processQueueItem($queueItem);
                    break;

                case 'server_wins':
                    $queueItem->update([
                        'status' => 'completed',
                        'resolution' => 'server_wins',
                        'resolved_by' => auth()->id(),
                        'resolved_at' => now(),
                        'processed_at' => now(),
                    ]);
                    $result = ['status' => 'resolved', 'resolution' => 'server_wins'];
                    break;

                case 'merge':
                    if (! $mergedData) {
                        throw new \Exception('Merged data required for merge resolution');
                    }
                    $queueItem->update(['data' => $mergedData, 'status' => 'pending']);
                    $result = $this->processQueueItem($queueItem);
                    break;

                default:
                    throw new \Exception('Invalid resolution strategy');
            }

            $queueItem->update([
                'resolution' => $resolution,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
