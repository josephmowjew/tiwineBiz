<?php

use App\Models\Branch;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use App\Policies\BranchPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new BranchPolicy;
});

// ViewAny Tests

test('shop owner can view any branches', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    expect($this->policy->viewAny($user))->toBeTrue();
});

test('shop member can view any branches', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    expect($this->policy->viewAny($member))->toBeTrue();
});

test('user with no shop access cannot view any branches', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse();
});

// View Tests

test('shop owner can view their branch', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    expect($this->policy->view($user, $branch))->toBeTrue();
});

test('shop member can view branch from their shop', function () {
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

    expect($this->policy->view($member, $branch))->toBeTrue();
});

test('user explicitly assigned to branch can view it', function () {
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

    expect($this->policy->view($user, $branch))->toBeTrue();
});

test('user cannot view branch they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    expect($this->policy->view($user1, $branch))->toBeFalse();
});

// Create Tests

test('shop owner can create branches for their shop', function () {
    $user = User::factory()->create();
    Shop::factory()->create(['owner_id' => $user->id]);

    expect($this->policy->create($user))->toBeTrue();
});

test('shop member cannot create branches', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    expect($this->policy->create($member))->toBeFalse();
});

test('user with no shop ownership cannot create branches', function () {
    $user = User::factory()->create();

    expect($this->policy->create($user))->toBeFalse();
});

// Update Tests

test('shop owner can update their branches', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    expect($this->policy->update($user, $branch))->toBeTrue();
});

test('shop member cannot update branches', function () {
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

    expect($this->policy->update($member, $branch))->toBeFalse();
});

test('user cannot update branch from shop they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    expect($this->policy->update($user1, $branch))->toBeFalse();
});

// Delete Tests

test('shop owner can delete non-main branches', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'branch_type' => 'satellite',
    ]);

    expect($this->policy->delete($user, $branch))->toBeTrue();
});

test('shop owner cannot delete main branch', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->main()->create(['shop_id' => $shop->id]);

    expect($this->policy->delete($user, $branch))->toBeFalse();
});

test('shop member cannot delete branches', function () {
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

    expect($this->policy->delete($member, $branch))->toBeFalse();
});

test('user cannot delete branch from shop they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);
    $branch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'branch_type' => 'satellite',
    ]);

    expect($this->policy->delete($user1, $branch))->toBeFalse();
});

// AssignUsers Tests

test('shop owner can assign users to branches', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    expect($this->policy->assignUsers($user, $branch))->toBeTrue();
});

test('branch manager can assign users to their branch', function () {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create([
        'shop_id' => $shop->id,
        'manager_id' => $manager->id,
    ]);

    expect($this->policy->assignUsers($manager, $branch))->toBeTrue();
});

test('shop member cannot assign users to branches', function () {
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

    expect($this->policy->assignUsers($member, $branch))->toBeFalse();
});

test('user cannot assign users to branch from shop they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);

    expect($this->policy->assignUsers($user1, $branch))->toBeFalse();
});
