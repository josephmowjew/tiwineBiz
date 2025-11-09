<?php

use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access shops', function () {
    $response = $this->getJson('/api/v1/shops');

    $response->assertUnauthorized();
});

test('authenticated user can list their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/shops');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'owner_id'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonFragment(['name' => $shop->name]);
});

test('user can only see shops they own or belong to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $ownShop = Shop::factory()->create(['owner_id' => $user1->id]);
    $otherShop = Shop::factory()->create(['owner_id' => $user2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/v1/shops');

    $response->assertOk()
        ->assertJsonFragment(['name' => $ownShop->name])
        ->assertJsonMissing(['name' => $otherShop->name]);
});

test('user can see shops they are member of', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson('/api/v1/shops');

    $response->assertOk()
        ->assertJsonFragment(['name' => $shop->name]);
});

test('user can create a shop', function () {
    $user = User::factory()->create();

    $shopData = [
        'name' => 'Test Shop',
        'business_type' => 'retail',
        'phone' => '+265999123456',
        'address' => '123 Test Street',
        'city' => 'Blantyre',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/shops', $shopData);

    $response->assertCreated()
        ->assertJsonFragment(['name' => 'Test Shop'])
        ->assertJsonFragment(['owner_id' => $user->id])
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'owner_id'],
        ]);

    $this->assertDatabaseHas('shops', [
        'name' => 'Test Shop',
        'owner_id' => $user->id,
    ]);
});

test('shop creation validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/shops', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'business_type', 'phone']);
});

test('shop creation validates business type', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/shops', [
            'name' => 'Test Shop',
            'business_type' => 'invalid_type',
            'phone' => '+265999123456',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['business_type']);
});

test('shop creation validates phone format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/shops', [
            'name' => 'Test Shop',
            'business_type' => 'retail',
            'phone' => 'invalid-phone',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['phone']);
});

test('user can view a specific shop they own', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/shops/{$shop->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $shop->name])
        ->assertJsonFragment(['id' => $shop->id]);
});

test('user can view shop with included relationships', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/shops/{$shop->id}?include=owner");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'owner' => ['id', 'name', 'email'],
            ],
        ]);
});

test('user cannot view shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/shops/{$shop->id}");

    $response->assertNotFound()
        ->assertJsonFragment(['message' => 'Shop not found or you do not have access to it.']);
});

test('user can update their own shop', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $updateData = [
        'name' => 'Updated Shop Name',
        'city' => 'Lilongwe',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/shops/{$shop->id}", $updateData);

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Updated Shop Name']);

    $this->assertDatabaseHas('shops', [
        'id' => $shop->id,
        'name' => 'Updated Shop Name',
        'city' => 'Lilongwe',
    ]);
});

test('user cannot update shop they do not own', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($member->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->putJson("/api/v1/shops/{$shop->id}", [
            'name' => 'Hacked Shop',
        ]);

    $response->assertNotFound();

    $this->assertDatabaseMissing('shops', [
        'id' => $shop->id,
        'name' => 'Hacked Shop',
    ]);
});

test('user can deactivate their own shop', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/shops/{$shop->id}");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Shop deactivated successfully.']);

    $this->assertDatabaseHas('shops', [
        'id' => $shop->id,
        'is_active' => false,
        'deactivation_reason' => 'Deleted by owner',
    ]);
});

test('user cannot delete shop they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->deleteJson("/api/v1/shops/{$shop->id}");

    $response->assertNotFound();

    $this->assertDatabaseHas('shops', [
        'id' => $shop->id,
        'is_active' => true,
    ]);
});

test('shops can be filtered by business type', function () {
    $user = User::factory()->create();
    $retailShop = Shop::factory()->create([
        'owner_id' => $user->id,
        'business_type' => 'retail',
    ]);
    $wholesaleShop = Shop::factory()->create([
        'owner_id' => $user->id,
        'business_type' => 'wholesale',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/shops?business_type=retail');

    $response->assertOk()
        ->assertJsonFragment(['name' => $retailShop->name])
        ->assertJsonMissing(['name' => $wholesaleShop->name]);
});

test('shops can be filtered by subscription status', function () {
    $user = User::factory()->create();
    $activeShop = Shop::factory()->create([
        'owner_id' => $user->id,
        'subscription_status' => 'active',
    ]);
    $trialShop = Shop::factory()->withTrial()->create([
        'owner_id' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/shops?subscription_status=active');

    $response->assertOk()
        ->assertJsonFragment(['name' => $activeShop->name])
        ->assertJsonMissing(['name' => $trialShop->name]);
});

test('shops can be searched by name', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create([
        'owner_id' => $user->id,
        'name' => 'Electronics Warehouse',
    ]);
    $shop2 = Shop::factory()->create([
        'owner_id' => $user->id,
        'name' => 'Food Market',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/shops?search=Electronics');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Electronics Warehouse'])
        ->assertJsonMissing(['name' => 'Food Market']);
});

test('shops list is paginated', function () {
    $user = User::factory()->create();
    Shop::factory()->count(20)->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/shops');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.per_page', 15);
});
