<?php

use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;

// Authentication Tests
test('unauthenticated user cannot access stock movements', function () {
    $response = $this->getJson('/api/v1/stock-movements');

    $response->assertUnauthorized();
});

test('authenticated user can list stock movements from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    StockMovement::factory()->count(3)->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/stock-movements');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('user cannot see stock movements from other shops', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $userShop = Shop::factory()->create(['owner_id' => $user->id]);
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);

    $product1 = Product::factory()->create(['shop_id' => $userShop->id]);
    $product2 = Product::factory()->create(['shop_id' => $otherShop->id]);

    StockMovement::factory()->create([
        'shop_id' => $userShop->id,
        'product_id' => $product1->id,
        'created_by' => $user->id,
    ]);
    StockMovement::factory()->create([
        'shop_id' => $otherShop->id,
        'product_id' => $product2->id,
        'created_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/stock-movements');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// Create Tests
test('authenticated user can create a stock movement', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase',
            'quantity' => 50,
            'unit_cost' => 1000.00,
            'reason' => null,
            'notes' => 'Restocking inventory',
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['movement_type' => 'purchase'])
        ->assertJsonFragment(['quantity' => '50.000'])
        ->assertJsonFragment(['quantity_before' => '100.000'])
        ->assertJsonFragment(['quantity_after' => '150.000']);

    $product->refresh();
    expect($product->quantity)->toBe('150.000');
});

test('stock decrease movement reduces product quantity', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'quantity' => 25,
            'unit_cost' => 1500.00,
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['quantity_before' => '100.000'])
        ->assertJsonFragment(['quantity_after' => '75.000']);

    $product->refresh();
    expect($product->quantity)->toBe('75.000');
});

test('total cost is calculated automatically', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase',
            'quantity' => 10,
            'unit_cost' => 2500.50,
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['total_cost' => '25005.00']);
});

// Validation Tests
test('shop_id is required to create stock movement', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'product_id' => $product->id,
            'movement_type' => 'purchase',
            'quantity' => 10,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['shop_id']);
});

test('product_id is required to create stock movement', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'shop_id' => $shop->id,
            'movement_type' => 'purchase',
            'quantity' => 10,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);
});

test('movement_type must be valid enum value', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'movement_type' => 'invalid_type',
            'quantity' => 10,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['movement_type']);
});

test('quantity is required and must be positive', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'movement_type' => 'purchase',
            'quantity' => -5,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);
});

test('reason is required for adjustment movements', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'movement_type' => 'adjustment_increase',
            'quantity' => 10,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

test('reason is required for damage movements', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'shop_id' => $shop->id,
            'product_id' => $product->id,
            'movement_type' => 'damage',
            'quantity' => 5,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

// Show Tests
test('authenticated user can view stock movement details', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $stockMovement = StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/stock-movements/{$stockMovement->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $stockMovement->id]);
});

test('user cannot view stock movement from another shop', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $product = Product::factory()->create(['shop_id' => $otherShop->id]);
    $stockMovement = StockMovement::factory()->create([
        'shop_id' => $otherShop->id,
        'product_id' => $product->id,
        'created_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/stock-movements/{$stockMovement->id}");

    $response->assertForbidden();
});

// Filtering Tests
test('can filter stock movements by shop', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user->id]);
    $product1 = Product::factory()->create(['shop_id' => $shop1->id]);
    $product2 = Product::factory()->create(['shop_id' => $shop2->id]);

    StockMovement::factory()->count(2)->create([
        'shop_id' => $shop1->id,
        'product_id' => $product1->id,
        'created_by' => $user->id,
    ]);
    StockMovement::factory()->create([
        'shop_id' => $shop2->id,
        'product_id' => $product2->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/stock-movements?shop_id={$shop1->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter stock movements by product', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product1 = Product::factory()->create(['shop_id' => $shop->id]);
    $product2 = Product::factory()->create(['shop_id' => $shop->id]);

    StockMovement::factory()->count(3)->create([
        'shop_id' => $shop->id,
        'product_id' => $product1->id,
        'created_by' => $user->id,
    ]);
    StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product2->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/stock-movements?product_id={$product1->id}");

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can filter stock movements by movement type', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    StockMovement::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'movement_type' => 'sale',
        'created_by' => $user->id,
    ]);
    StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'movement_type' => 'purchase',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/stock-movements?movement_type=sale');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter stock movements by date range', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
        'created_at' => now()->subDays(10),
    ]);
    StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
        'created_at' => now()->subDays(5),
    ]);
    StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
        'created_at' => now(),
    ]);

    $fromDate = now()->subDays(7)->toDateString();
    $toDate = now()->toDateString();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/stock-movements?from_date={$fromDate}&to_date={$toDate}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter stock movements by reference', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $referenceId = fake()->uuid();

    StockMovement::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'reference_type' => 'sale',
        'reference_id' => $referenceId,
        'created_by' => $user->id,
    ]);
    StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'reference_type' => 'purchase_order',
        'reference_id' => fake()->uuid(),
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/stock-movements?reference_type=sale&reference_id={$referenceId}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

// Sorting Tests
test('stock movements are sorted by created_at desc by default', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $movement1 = StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
        'created_at' => now()->subDays(2),
    ]);
    $movement2 = StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/stock-movements');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($movement2->id)
        ->and($data[1]['id'])->toBe($movement1->id);
});

test('can sort stock movements in ascending order', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $movement1 = StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
        'created_at' => now()->subDays(2),
    ]);
    $movement2 = StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/stock-movements?sort_order=asc');

    $response->assertOk();
    $data = $response->json('data');
    expect($data[0]['id'])->toBe($movement1->id)
        ->and($data[1]['id'])->toBe($movement2->id);
});

// Pagination Test
test('stock movements are paginated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    StockMovement::factory()->count(20)->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/stock-movements');

    $response->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

// Immutability Tests
test('stock movements cannot be updated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $stockMovement = StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/stock-movements/{$stockMovement->id}", [
            'quantity' => 999,
        ]);

    $response->assertMethodNotAllowed();
});

test('stock movements cannot be deleted', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $stockMovement = StockMovement::factory()->create([
        'shop_id' => $shop->id,
        'product_id' => $product->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/stock-movements/{$stockMovement->id}");

    $response->assertMethodNotAllowed();
});

// Access Control Tests
test('user cannot create stock movement for product in another shop', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $otherProduct = Product::factory()->create(['shop_id' => $otherShop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/stock-movements', [
            'shop_id' => $shop->id,
            'product_id' => $otherProduct->id,
            'movement_type' => 'purchase',
            'quantity' => 10,
        ]);

    $response->assertNotFound();
});
