<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Repositories\Contracts\BranchRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BranchController extends Controller
{
    public function __construct(
        protected BranchRepositoryInterface $branchRepository
    ) {
        //
    }

    /**
     * Display a listing of branches.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'shop_id',
            'is_active',
            'branch_type',
            'manager_id',
            'search',
        ]);

        $branches = $this->branchRepository->autoPaginate(
            $request->input('per_page'),
            $filters
        );

        return BranchResource::collection($branches);
    }

    /**
     * Store a newly created branch.
     */
    public function store(StoreBranchRequest $request): BranchResource
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $branch = $this->branchRepository->create($data);

        return new BranchResource($branch);
    }

    /**
     * Display the specified branch.
     */
    public function show(Request $request, Branch $branch): BranchResource
    {
        // Check if user has access to this branch
        $user = $request->user();
        $accessibleBranchIds = $user->getAccessibleBranchIds();

        if (! $accessibleBranchIds->contains($branch->id)) {
            abort(404);
        }

        $branch->load(['shop', 'manager', 'branchUsers']);

        return new BranchResource($branch);
    }

    /**
     * Update the specified branch.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): BranchResource
    {
        // Check if user has access to this branch's shop (already done in UpdateBranchRequest)
        // But also verify branch access for 404
        $user = $request->user();
        $accessibleBranchIds = $user->getAccessibleBranchIds();

        if (! $accessibleBranchIds->contains($branch->id)) {
            abort(404);
        }

        $data = $request->validated();

        $branch = $this->branchRepository->update($branch->id, $data);

        return new BranchResource($branch);
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        // Check if user has access to this branch's shop
        $user = $request->user();
        $accessibleBranchIds = $user->getAccessibleBranchIds();

        if (! $accessibleBranchIds->contains($branch->id)) {
            abort(404);
        }

        // Check if user is the shop owner
        if ($branch->shop->owner_id !== $user->id) {
            abort(403, 'Only shop owner can delete branches');
        }

        // Check if it's not the main branch
        if ($branch->branch_type === 'main') {
            abort(403, 'Cannot delete main branch');
        }

        $this->branchRepository->delete($branch->id);

        return response()->json([
            'message' => 'Branch deleted successfully',
        ]);
    }

    /**
     * Assign user to branch.
     */
    public function assignUser(Request $request, Branch $branch): JsonResponse
    {
        // Check if user has access to this branch
        $user = $request->user();
        $accessibleBranchIds = $user->getAccessibleBranchIds();

        if (! $accessibleBranchIds->contains($branch->id)) {
            abort(404);
        }

        // Check if user is shop owner or branch manager
        if ($branch->shop->owner_id !== $user->id && $branch->manager_id !== $user->id) {
            abort(403, 'Only shop owner or branch manager can assign users');
        }

        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
            'can_view_reports' => ['boolean'],
            'can_manage_stock' => ['boolean'],
            'can_process_sales' => ['boolean'],
            'can_manage_customers' => ['boolean'],
            'permissions' => ['nullable', 'array'],
        ]);

        $this->branchRepository->assignUser(
            $branch->id,
            $request->input('user_id'),
            $request->input('role_id'),
            $request->only([
                'can_view_reports',
                'can_manage_stock',
                'can_process_sales',
                'can_manage_customers',
                'permissions',
            ])
        );

        return response()->json([
            'message' => 'User assigned to branch successfully',
        ]);
    }

    /**
     * Remove user from branch.
     */
    public function removeUser(Request $request, Branch $branch): JsonResponse
    {
        // Check if user has access to this branch
        $user = $request->user();
        $accessibleBranchIds = $user->getAccessibleBranchIds();

        if (! $accessibleBranchIds->contains($branch->id)) {
            abort(404);
        }

        // Check if user is shop owner or branch manager
        if ($branch->shop->owner_id !== $user->id && $branch->manager_id !== $user->id) {
            abort(403, 'Only shop owner or branch manager can remove users');
        }

        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $this->branchRepository->removeUser(
            $branch->id,
            $request->input('user_id')
        );

        return response()->json([
            'message' => 'User removed from branch successfully',
        ]);
    }

    /**
     * Get users assigned to branch.
     */
    public function users(Request $request, Branch $branch): JsonResponse
    {
        // Check if user has access to this branch
        $user = $request->user();
        $accessibleBranchIds = $user->getAccessibleBranchIds();

        if (! $accessibleBranchIds->contains($branch->id)) {
            abort(404);
        }

        $users = $this->branchRepository->getBranchUsers($branch->id);

        return response()->json([
            'data' => $users,
        ]);
    }
}
