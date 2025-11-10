<?php

use App\Models\Branch;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\BranchRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->shop = Shop::factory()->create(['owner_id' => $this->user->id]);
    $this->actingAs($this->user, 'sanctum');
    $this->repository = app(BranchRepository::class);
});

// Filter Tests

test('it filters by shop_id', function () {
    $shop2 = Shop::factory()->create(['owner_id' => $this->user->id]);

    $branch1 = Branch::factory()->create(['shop_id' => $this->shop->id]);
    $branch2 = Branch::factory()->create(['shop_id' => $shop2->id]);

    $branches = $this->repository->all(['shop_id' => $this->shop->id]);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($branch1->id);
});

test('it filters by is_active', function () {
    $activeBranch = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'is_active' => true,
    ]);
    $inactiveBranch = Branch::factory()->inactive()->create([
        'shop_id' => $this->shop->id,
    ]);

    $branches = $this->repository->all(['is_active' => true]);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($activeBranch->id);
});

test('it filters by branch_type', function () {
    $mainBranch = Branch::factory()->main()->create(['shop_id' => $this->shop->id]);
    $satelliteBranch = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_type' => 'satellite',
    ]);

    $branches = $this->repository->all(['branch_type' => 'main']);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($mainBranch->id);
});

test('it filters by manager_id', function () {
    $manager = User::factory()->create();

    $managedBranch = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'manager_id' => $manager->id,
    ]);
    $unmanagedBranch = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'manager_id' => null,
    ]);

    $branches = $this->repository->all(['manager_id' => $manager->id]);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($managedBranch->id);
});

test('search filters by name', function () {
    $branch1 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Main Branch Downtown',
    ]);
    $branch2 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Warehouse North',
    ]);

    $branches = $this->repository->all(['search' => 'Downtown']);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($branch1->id);
});

test('search filters by code', function () {
    $branch1 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'code' => 'MAIN001',
    ]);
    $branch2 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'code' => 'SAT002',
    ]);

    $branches = $this->repository->all(['search' => 'MAIN001']);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($branch1->id);
});

test('search filters by city', function () {
    $branch1 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'city' => 'Blantyre',
    ]);
    $branch2 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'city' => 'Lilongwe',
    ]);

    $branches = $this->repository->all(['search' => 'Blantyre']);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($branch1->id);
});

test('it combines multiple filters', function () {
    $manager = User::factory()->create();

    $branch1 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Main Branch',
        'branch_type' => 'main',
        'manager_id' => $manager->id,
        'is_active' => true,
    ]);
    $branch2 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Main Warehouse',
        'branch_type' => 'main',
        'manager_id' => $manager->id,
        'is_active' => false,
    ]);
    $branch3 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Satellite Branch',
        'branch_type' => 'satellite',
        'manager_id' => $manager->id,
        'is_active' => true,
    ]);

    $branches = $this->repository->all([
        'search' => 'Main',
        'branch_type' => 'main',
        'manager_id' => $manager->id,
        'is_active' => true,
    ]);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($branch1->id);
});

// Scoping Tests

test('it only returns branches from accessible shops', function () {
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $accessibleBranch = Branch::factory()->create(['shop_id' => $this->shop->id]);
    $inaccessibleBranch = Branch::factory()->create(['shop_id' => $shop2->id]);

    $branches = $this->repository->all();

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($accessibleBranch->id);
});

test('it returns branches from shops user is member of', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($this->user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $branches = $this->repository->all();

    expect($branches->pluck('id'))->toContain($branch->id);
});

test('it returns branches user is explicitly assigned to', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $branch->users()->attach($this->user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $branches = $this->repository->all();

    expect($branches->pluck('id'))->toContain($branch->id);
});

// FindByShop Tests

test('findByShop returns branches for specific shop', function () {
    $shop2 = Shop::factory()->create(['owner_id' => $this->user->id]);

    $branch1 = Branch::factory()->create(['shop_id' => $this->shop->id]);
    $branch2 = Branch::factory()->create(['shop_id' => $shop2->id]);

    $branches = $this->repository->findByShop($this->shop->id);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($branch1->id);
});

test('findByShop respects shop access control', function () {
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    Branch::factory()->create(['shop_id' => $shop2->id]);

    $branches = $this->repository->findByShop($shop2->id);

    expect($branches)->toHaveCount(0);
});

// GetActiveBranches Tests

test('getActiveBranches returns only active branches for shop', function () {
    $activeBranch = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'is_active' => true,
    ]);
    $inactiveBranch = Branch::factory()->inactive()->create([
        'shop_id' => $this->shop->id,
    ]);

    $branches = $this->repository->getActiveBranches($this->shop->id);

    expect($branches)->toHaveCount(1);
    expect($branches->first()->id)->toBe($activeBranch->id);
});

// GetMainBranch Tests

test('getMainBranch returns main branch for shop', function () {
    $mainBranch = Branch::factory()->main()->create(['shop_id' => $this->shop->id]);
    $satelliteBranch = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_type' => 'satellite',
    ]);

    $branch = $this->repository->getMainBranch($this->shop->id);

    expect($branch->id)->toBe($mainBranch->id);
});

test('getMainBranch returns null when no main branch exists', function () {
    Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_type' => 'satellite',
    ]);

    $branch = $this->repository->getMainBranch($this->shop->id);

    expect($branch)->toBeNull();
});

test('getMainBranch respects shop access control', function () {
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    Branch::factory()->main()->create(['shop_id' => $shop2->id]);

    $branch = $this->repository->getMainBranch($shop2->id);

    expect($branch)->toBeNull();
});

// AssignUser Tests

test('assignUser creates new branch user assignment', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['shop_id' => $this->shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $result = $this->repository->assignUser(
        $branch->id,
        $user->id,
        $cashierRole->id,
        [
            'can_view_reports' => true,
            'can_manage_stock' => false,
            'can_process_sales' => true,
        ]
    );

    expect($result)->toBeTrue();

    $this->assertDatabaseHas('branch_user', [
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'role_id' => $cashierRole->id,
        'can_view_reports' => true,
        'can_manage_stock' => false,
        'can_process_sales' => true,
    ]);
});

test('assignUser updates existing branch user assignment', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['shop_id' => $this->shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();
    $managerRole = Role::where('name', 'manager')->first();

    $branch->users()->attach($user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'can_view_reports' => false,
        'assigned_at' => now(),
    ]);

    $result = $this->repository->assignUser(
        $branch->id,
        $user->id,
        $managerRole->id,
        [
            'can_view_reports' => true,
        ]
    );

    expect($result)->toBeTrue();

    $this->assertDatabaseHas('branch_user', [
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'role_id' => $managerRole->id,
        'can_view_reports' => true,
    ]);
});

// RemoveUser Tests

test('removeUser removes user from branch', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['shop_id' => $this->shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $branch->users()->attach($user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $result = $this->repository->removeUser($branch->id, $user->id);

    expect($result)->toBeTrue();

    $this->assertDatabaseMissing('branch_user', [
        'branch_id' => $branch->id,
        'user_id' => $user->id,
    ]);
});

test('removeUser returns false when user is not assigned', function () {
    $user = User::factory()->create();
    $branch = Branch::factory()->create(['shop_id' => $this->shop->id]);

    $result = $this->repository->removeUser($branch->id, $user->id);

    expect($result)->toBeFalse();
});

// GetBranchUsers Tests

test('getBranchUsers returns users assigned to branch', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $branch = Branch::factory()->create(['shop_id' => $this->shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $branch->users()->attach($user1->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);
    $branch->users()->attach($user2->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $users = $this->repository->getBranchUsers($branch->id);

    expect($users)->toHaveCount(2);
    expect($users->pluck('id'))->toContain($user1->id, $user2->id);
});

test('getBranchUsers excludes inactive user assignments', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $branch = Branch::factory()->create(['shop_id' => $this->shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $branch->users()->attach($user1->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);
    $branch->users()->attach($user2->id, [
        'role_id' => $cashierRole->id,
        'is_active' => false,
        'assigned_at' => now(),
    ]);

    $users = $this->repository->getBranchUsers($branch->id);

    expect($users)->toHaveCount(1);
    expect($users->first()->id)->toBe($user1->id);
});

test('getBranchUsers returns empty collection when no users assigned', function () {
    $branch = Branch::factory()->create(['shop_id' => $this->shop->id]);

    $users = $this->repository->getBranchUsers($branch->id);

    expect($users)->toHaveCount(0);
});

// Eager Loading Tests

test('it eager loads relationships when specified', function () {
    $manager = User::factory()->create();
    $branch = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'manager_id' => $manager->id,
    ]);

    $branches = $this->repository->all(['with' => 'shop,manager']);

    expect($branches->first()->relationLoaded('shop'))->toBeTrue();
    expect($branches->first()->relationLoaded('manager'))->toBeTrue();
});

// Sorting Tests

test('it sorts by specified column and direction', function () {
    $branch1 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Zebra Branch',
    ]);
    $branch2 = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Alpha Branch',
    ]);

    $branches = $this->repository->all([
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]);

    expect($branches->first()->id)->toBe($branch2->id);
    expect($branches->last()->id)->toBe($branch1->id);
});
