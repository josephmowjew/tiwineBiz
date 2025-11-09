<?php

use App\Models\Customer;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access customers', function () {
    $response = $this->getJson('/api/v1/customers');

    $response->assertUnauthorized();
});

test('authenticated user can list customers from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/customers');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'shop_id'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonFragment(['name' => $customer->name]);
});

test('user can only see customers from shops they have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shop1 = Shop::factory()->create(['owner_id' => $user1->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $customer1 = Customer::factory()->create([
        'shop_id' => $shop1->id,
        'created_by' => $user1->id,
    ]);
    $customer2 = Customer::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/v1/customers');

    $response->assertOk()
        ->assertJsonFragment(['name' => $customer1->name])
        ->assertJsonMissing(['name' => $customer2->name]);
});

test('user can create a customer', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $customerData = [
        'shop_id' => $shop->id,
        'name' => 'John Doe',
        'phone' => '+265999123456',
        'email' => 'john@example.com',
        'credit_limit' => 50000.00,
        'trust_level' => 'trusted',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/customers', $customerData);

    $response->assertCreated()
        ->assertJsonFragment(['name' => 'John Doe'])
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'shop_id'],
        ]);

    $this->assertDatabaseHas('customers', [
        'name' => 'John Doe',
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
});

test('user cannot create customer for shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $customerData = [
        'shop_id' => $shop2->id,
        'name' => 'Unauthorized Customer',
        'phone' => '+265999123456',
    ];

    $response = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/customers', $customerData);

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('user can view a specific customer', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/customers/{$customer->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $customer->name])
        ->assertJsonFragment(['id' => $customer->id]);
});

test('user can view customer with included relationships', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/customers/{$customer->id}?include=shop");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'shop' => ['id', 'name'],
            ],
        ]);
});

test('user cannot view customer from shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/customers/{$customer->id}");

    $response->assertNotFound()
        ->assertJsonFragment(['message' => 'Customer not found or you do not have access to it.']);
});

test('user can update a customer', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $updateData = [
        'name' => 'Updated Customer Name',
        'credit_limit' => 100000.00,
        'trust_level' => 'trusted',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/customers/{$customer->id}", $updateData);

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Updated Customer Name']);

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Updated Customer Name',
    ]);
});

test('user cannot update customer from shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->putJson("/api/v1/customers/{$customer->id}", [
            'name' => 'Hacked Customer',
        ]);

    $response->assertNotFound();
});

test('user can deactivate a customer', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/customers/{$customer->id}");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Customer deactivated successfully.']);

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'is_active' => false,
    ]);
});

test('customers can be filtered by trust level', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $trustedCustomer = Customer::factory()->trusted()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $newCustomer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'trust_level' => 'new',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/customers?trust_level=trusted');

    $response->assertOk()
        ->assertJsonFragment(['name' => $trustedCustomer->name])
        ->assertJsonMissing(['name' => $newCustomer->name]);
});

test('customers can be filtered by active status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $activeCustomer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $inactiveCustomer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'is_active' => false,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/customers?is_active=true');

    $response->assertOk()
        ->assertJsonFragment(['name' => $activeCustomer->name])
        ->assertJsonMissing(['name' => $inactiveCustomer->name]);
});

test('customers can be filtered by blocked status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $blockedCustomer = Customer::factory()->blocked()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $normalCustomer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'blocked_at' => null,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/customers?is_blocked=true');

    $response->assertOk()
        ->assertJsonFragment(['name' => $blockedCustomer->name])
        ->assertJsonMissing(['name' => $normalCustomer->name]);
});

test('customers can be searched by name, phone, or email', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $customer1 = Customer::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Alice Johnson',
        'phone' => '+265999123456',
        'created_by' => $user->id,
    ]);

    $customer2 = Customer::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Bob Smith',
        'phone' => '+265888654321',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/customers?search=Alice');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Alice Johnson'])
        ->assertJsonMissing(['name' => 'Bob Smith']);
});

test('customers can be filtered by specific shop', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user->id]);

    $customer1 = Customer::factory()->create([
        'shop_id' => $shop1->id,
        'created_by' => $user->id,
    ]);
    $customer2 = Customer::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/customers?shop_id={$shop1->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $customer1->name])
        ->assertJsonMissing(['name' => $customer2->name]);
});

test('user cannot filter customers by shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/customers?shop_id={$shop2->id}");

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('customers can be sorted', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $customerA = Customer::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'A Customer',
        'created_by' => $user->id,
    ]);

    $customerZ = Customer::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Z Customer',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/customers?sort_by=name&sort_order=asc');

    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['name'])->toBe('A Customer');
});

test('customers list is paginated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Customer::factory()->count(20)->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/customers');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.per_page', 15);
});
