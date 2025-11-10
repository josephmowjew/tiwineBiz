<?php

use App\Models\Branch;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Authentication Tests

test('unauthenticated user cannot access branches', function () {
    $response = $this->getJson('/api/v1/branches');

    $response->assertUnauthorized();
});

// Index Endpoint Tests

test('authenticated user can list their accessible branches', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/branches');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'code', 'branch_type', 'shop_id'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonFragment(['name' => $branch->name]);
});

test('user can only see branches from shops they have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shop1 = Shop::factory()->create(['owner_id' => $user1->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $branch1 = Branch::factory()->create(['shop_id' => $shop1->id]);
    $branch2 = Branch::factory()->create(['shop_id' => $shop2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/v1/branches');

    $response->assertOk()
        ->assertJsonFragment(['name' => $branch1->name])
        ->assertJsonMissing(['name' => $branch2->name]);
});

test('user can see branches from shops they are member of', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson('/api/v1/branches');

    $response->assertOk()
        ->assertJsonFragment(['name' => $branch->name]);
});

test('user can see branches they are explicitly assigned to', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $branch->users()->attach($user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/branches');

    $response->assertOk()
        ->assertJsonFragment(['name' => $branch->name]);
});

test('branches can be filtered by shop_id', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user->id]);

    $branch1 = Branch::factory()->create(['shop_id' => $shop1->id]);
    $branch2 = Branch::factory()->create(['shop_id' => $shop2->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/branches?shop_id={$shop1->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $branch1->name])
        ->assertJsonMissing(['name' => $branch2->name]);
});

test('branches can be filtered by is_active', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $activeBranch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'is_active' => true,
    ]);
    $inactiveBranch = Branch::factory()->inactive()->create([
        'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/branches?is_active=1');

    $response->assertOk()
        ->assertJsonFragment(['name' => $activeBranch->name])
        ->assertJsonMissing(['name' => $inactiveBranch->name]);
});

test('branches can be filtered by branch_type', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $mainBranch = Branch::factory()->main()->create(['shop_id' => $shop->id]);
    $satelliteBranch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'branch_type' => 'satellite',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/branches?branch_type=main');

    $response->assertOk()
        ->assertJsonFragment(['name' => $mainBranch->name])
        ->assertJsonMissing(['name' => $satelliteBranch->name]);
});

test('branches can be filtered by manager_id', function () {
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $managedBranch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'manager_id' => $manager->id,
    ]);
    $unmanagedBranch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'manager_id' => null,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/branches?manager_id={$manager->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $managedBranch->name])
        ->assertJsonMissing(['name' => $unmanagedBranch->name]);
});

test('branches can be searched by name', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $branch1 = Branch::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Main Branch Downtown',
    ]);
    $branch2 = Branch::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Warehouse North',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/branches?search=Downtown');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Main Branch Downtown'])
        ->assertJsonMissing(['name' => 'Warehouse North']);
});

test('branches list is paginated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    Branch::factory()->count(20)->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/branches');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.per_page', 15);
});

// Store Endpoint Tests

test('shop owner can create a branch', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $branchData = [
        'shop_id' => $shop->id,
        'name' => 'New Branch',
        'code' => 'NB001',
        'branch_type' => 'satellite',
        'phone' => '+265999123456',
        'address' => '123 Test Street',
        'city' => 'Blantyre',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/branches', $branchData);

    $response->assertCreated()
        ->assertJsonFragment(['name' => 'New Branch'])
        ->assertJsonFragment(['shop_id' => $shop->id])
        ->assertJsonStructure([
            'data' => ['id', 'name', 'code', 'branch_type', 'shop_id'],
        ]);

    $this->assertDatabaseHas('branches', [
        'name' => 'New Branch',
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
});

test('shop member cannot create a branch', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $branchData = [
        'shop_id' => $shop->id,
        'name' => 'New Branch',
        'code' => 'NB001',
        'branch_type' => 'satellite',
    ];

    $response = $this->actingAs($member, 'sanctum')
        ->postJson('/api/v1/branches', $branchData);

    $response->assertForbidden();
});

test('branch creation validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/branches', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['shop_id', 'name', 'code', 'branch_type']);
});

test('branch creation validates branch_type enum', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/branches', [
            'shop_id' => $shop->id,
            'name' => 'Test Branch',
            'code' => 'TB001',
            'branch_type' => 'invalid_type',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['branch_type']);
});

test('branch creation validates email format', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/branches', [
            'shop_id' => $shop->id,
            'name' => 'Test Branch',
            'code' => 'TB001',
            'branch_type' => 'satellite',
            'email' => 'invalid-email',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('branch creation validates latitude range', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/branches', [
            'shop_id' => $shop->id,
            'name' => 'Test Branch',
            'code' => 'TB001',
            'branch_type' => 'satellite',
            'latitude' => 100, // Invalid: should be between -90 and 90
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);
});

test('branch creation validates longitude range', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/branches', [
            'shop_id' => $shop->id,
            'name' => 'Test Branch',
            'code' => 'TB001',
            'branch_type' => 'satellite',
            'longitude' => 200, // Invalid: should be between -180 and 180
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['longitude']);
});

// Show Endpoint Tests

test('user can view a specific branch they have access to', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/branches/{$branch->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $branch->name])
        ->assertJsonFragment(['id' => $branch->id]);
});

test('user can view branch with included relationships', function () {
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'manager_id' => $manager->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/branches/{$branch->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'manager' => ['id', 'name', 'email'],
            ],
        ]);
});

test('user cannot view branch they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/branches/{$branch->id}");

    $response->assertNotFound();
});

// Update Endpoint Tests

test('shop owner can update their branch', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    $updateData = [
        'name' => 'Updated Branch Name',
        'city' => 'Lilongwe',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/branches/{$branch->id}", $updateData);

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Updated Branch Name']);

    $this->assertDatabaseHas('branches', [
        'id' => $branch->id,
        'name' => 'Updated Branch Name',
        'city' => 'Lilongwe',
    ]);
});

test('shop member cannot update branch', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->putJson("/api/v1/branches/{$branch->id}", [
            'name' => 'Hacked Branch',
        ]);

    $response->assertForbidden();
});

test('user cannot update branch from shop they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->putJson("/api/v1/branches/{$branch->id}", [
            'name' => 'Hacked Branch',
        ]);

    $response->assertNotFound();

    $this->assertDatabaseMissing('branches', [
        'id' => $branch->id,
        'name' => 'Hacked Branch',
    ]);
});

// Delete Endpoint Tests

test('shop owner can delete non-main branch', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'branch_type' => 'satellite',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/branches/{$branch->id}");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Branch deleted successfully']);

    $this->assertDatabaseMissing('branches', [
        'id' => $branch->id,
    ]);
});

test('shop owner cannot delete main branch', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->main()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/branches/{$branch->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('branches', [
        'id' => $branch->id,
    ]);
});

test('shop member cannot delete branch', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'branch_type' => 'satellite',
    ]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->deleteJson("/api/v1/branches/{$branch->id}");

    $response->assertForbidden();
});

test('user cannot delete branch from shop they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);
    $branch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'branch_type' => 'satellite',
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->deleteJson("/api/v1/branches/{$branch->id}");

    $response->assertNotFound();

    $this->assertDatabaseHas('branches', [
        'id' => $branch->id,
    ]);
});

// User Assignment Endpoint Tests

test('shop owner can assign user to branch', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $assignData = [
        'user_id' => $user->id,
        'role_id' => $cashierRole->id,
        'can_view_reports' => true,
        'can_manage_stock' => false,
        'can_process_sales' => true,
        'can_manage_customers' => false,
    ];

    $response = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/branches/{$branch->id}/users", $assignData);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'User assigned to branch successfully']);

    $this->assertDatabaseHas('branch_user', [
        'branch_id' => $branch->id,
        'user_id' => $user->id,
        'role_id' => $cashierRole->id,
        'can_view_reports' => true,
        'can_manage_stock' => false,
    ]);
});

test('branch manager can assign user to their branch', function () {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'manager_id' => $manager->id,
    ]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $assignData = [
        'user_id' => $user->id,
        'role_id' => $cashierRole->id,
    ];

    $response = $this->actingAs($manager, 'sanctum')
        ->postJson("/api/v1/branches/{$branch->id}/users", $assignData);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'User assigned to branch successfully']);
});

test('regular shop member cannot assign users to branch', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $assignData = [
        'user_id' => $user->id,
        'role_id' => $cashierRole->id,
    ];

    $response = $this->actingAs($member, 'sanctum')
        ->postJson("/api/v1/branches/{$branch->id}/users", $assignData);

    $response->assertForbidden();
});

test('user assignment validates required fields', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/branches/{$branch->id}/users", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id', 'role_id']);
});

test('user assignment validates user_id exists', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $response = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/branches/{$branch->id}/users", [
            'user_id' => '00000000-0000-0000-0000-000000000000',
            'role_id' => $cashierRole->id,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id']);
});

test('user assignment validates role_id exists', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/branches/{$branch->id}/users", [
            'user_id' => $user->id,
            'role_id' => '00000000-0000-0000-0000-000000000000',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role_id']);
});

// User Removal Endpoint Tests

test('shop owner can remove user from branch', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $branch->users()->attach($user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $response = $this->actingAs($owner, 'sanctum')
        ->deleteJson("/api/v1/branches/{$branch->id}/users", [
            'user_id' => $user->id,
        ]);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'User removed from branch successfully']);

    $this->assertDatabaseMissing('branch_user', [
        'branch_id' => $branch->id,
        'user_id' => $user->id,
    ]);
});

test('branch manager can remove user from their branch', function () {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'manager_id' => $manager->id,
    ]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $branch->users()->attach($user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $response = $this->actingAs($manager, 'sanctum')
        ->deleteJson("/api/v1/branches/{$branch->id}/users", [
            'user_id' => $user->id,
        ]);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'User removed from branch successfully']);
});

test('regular shop member cannot remove users from branch', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $branch->users()->attach($user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->deleteJson("/api/v1/branches/{$branch->id}/users", [
            'user_id' => $user->id,
        ]);

    $response->assertForbidden();
});

// Get Branch Users Endpoint Tests

test('shop owner can view branch users', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $branch->users()->attach($user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $response = $this->actingAs($owner, 'sanctum')
        ->getJson("/api/v1/branches/{$branch->id}/users");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email'],
            ],
        ]);
});

test('shop member can view branch users', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson("/api/v1/branches/{$branch->id}/users");

    $response->assertOk()
        ->assertJsonStructure([
            'data',
        ]);
});

test('user cannot view users from branch they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/branches/{$branch->id}/users");

    $response->assertNotFound();
});
