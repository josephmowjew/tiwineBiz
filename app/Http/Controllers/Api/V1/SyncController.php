<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SyncQueue;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(
        protected SyncService $syncService
    ) {}

    /**
     * Push changes from client to server.
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string|max:100',
            'changes' => 'required|array',
            'changes.*.entity_type' => 'required|string|in:sale,product,customer,payment,credit',
            'changes.*.entity_id' => 'required|uuid',
            'changes.*.action' => 'required|string|in:create,update,delete',
            'changes.*.data' => 'required|array',
            'changes.*.timestamp' => 'nullable|date',
            'changes.*.priority' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            $results = $this->syncService->push(
                $request->input('changes'),
                $request->input('device_id'),
                $request->user()->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Sync push completed',
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync push failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pull changes from server to client.
     */
    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'last_sync_timestamp' => 'required|date',
            'entity_types' => 'nullable|array',
            'entity_types.*' => 'string|in:sale,product,customer,payment,credit',
        ]);

        try {
            $data = $this->syncService->pull(
                $request->input('last_sync_timestamp'),
                $request->input('entity_types')
            );

            return response()->json([
                'success' => true,
                'message' => 'Sync pull completed',
                'data' => $data,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync pull failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status for current user's shop.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $shop = $user->shops()->first() ?? $user->ownedShops()->first();

        if (! $shop) {
            return response()->json([
                'success' => false,
                'message' => 'No shop found for user',
            ], 404);
        }

        $status = $this->syncService->getSyncStatus($shop->id);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Get pending sync items.
     */
    public function pending(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $shop = $user->shops()->first() ?? $user->ownedShops()->first();

        if (! $shop) {
            return response()->json([
                'success' => false,
                'message' => 'No shop found for user',
            ], 404);
        }

        $pending = SyncQueue::where('shop_id', $shop->id)
            ->where('status', 'pending')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pending,
        ]);
    }

    /**
     * Get conflicted sync items.
     */
    public function conflicts(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $shop = $user->shops()->first() ?? $user->ownedShops()->first();

        if (! $shop) {
            return response()->json([
                'success' => false,
                'message' => 'No shop found for user',
            ], 404);
        }

        $conflicts = SyncQueue::where('shop_id', $shop->id)
            ->where('status', 'conflict')
            ->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $conflicts,
        ]);
    }

    /**
     * Resolve a sync conflict.
     */
    public function resolveConflict(Request $request, string $queueItemId): JsonResponse
    {
        $request->validate([
            'resolution' => 'required|string|in:client_wins,server_wins,merge',
            'merged_data' => 'nullable|array',
        ]);

        try {
            $result = $this->syncService->resolveConflict(
                $queueItemId,
                $request->input('resolution'),
                $request->input('merged_data')
            );

            return response()->json([
                'success' => true,
                'message' => 'Conflict resolved successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve conflict',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Retry failed sync items.
     */
    public function retry(Request $request, string $queueItemId): JsonResponse
    {
        $user = $request->user();
        $shop = $user->shops()->first() ?? $user->ownedShops()->first();

        if (! $shop) {
            return response()->json([
                'success' => false,
                'message' => 'No shop found for user',
            ], 404);
        }

        $queueItem = SyncQueue::where('shop_id', $shop->id)
            ->where('id', $queueItemId)
            ->first();

        if (! $queueItem) {
            return response()->json([
                'success' => false,
                'message' => 'Queue item not found',
            ], 404);
        }

        if (! in_array($queueItem->status, ['failed', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Item cannot be retried',
            ], 400);
        }

        try {
            $queueItem->update([
                'status' => 'pending',
                'error_message' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item queued for retry',
                'data' => $queueItem,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a sync queue item.
     */
    public function delete(Request $request, string $queueItemId): JsonResponse
    {
        $user = $request->user();
        $shop = $user->shops()->first() ?? $user->ownedShops()->first();

        if (! $shop) {
            return response()->json([
                'success' => false,
                'message' => 'No shop found for user',
            ], 404);
        }

        $queueItem = SyncQueue::where('shop_id', $shop->id)
            ->where('id', $queueItemId)
            ->first();

        if (! $queueItem) {
            return response()->json([
                'success' => false,
                'message' => 'Queue item not found',
            ], 404);
        }

        $queueItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Queue item deleted',
        ]);
    }

    /**
     * Get sync history.
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|in:completed,failed,conflict',
        ]);

        $user = $request->user();
        $shop = $user->shops()->first() ?? $user->ownedShops()->first();

        if (! $shop) {
            return response()->json([
                'success' => false,
                'message' => 'No shop found for user',
            ], 404);
        }

        $query = SyncQueue::where('shop_id', $shop->id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $history = $query->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 50))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}
