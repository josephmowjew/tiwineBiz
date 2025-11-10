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
        $this->authorizeResource(Branch::class, 'branch');
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
    public function show(Branch $branch): BranchResource
    {
        $branch->load(['shop', 'manager', 'branchUsers']);

        return new BranchResource($branch);
    }

    /**
     * Update the specified branch.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): BranchResource
    {
        $data = $request->validated();

        $branch = $this->branchRepository->update($branch->id, $data);

        return new BranchResource($branch);
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch): JsonResponse
    {
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
        $this->authorize('assignUsers', $branch);

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
        $this->authorize('assignUsers', $branch);

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
    public function users(Branch $branch): JsonResponse
    {
        $this->authorize('view', $branch);

        $users = $this->branchRepository->getBranchUsers($branch->id);

        return response()->json([
            'data' => $users,
        ]);
    }
}
