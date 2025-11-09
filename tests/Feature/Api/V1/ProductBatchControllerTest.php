<?php

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;

// Auth Tests
test('unauthenticated user cannot access product batches', function () {
    $response = $this->getJson('/api/v1/product-batches');
    $response->assertUnauthorized();
});

test('authenticated user can list product batches from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    ProductBatch::factory()->count(2)->create([
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/product-batches');

    $response->assertOk()->assertJsonCount(2, 'data');
});

// Create Tests
test('authenticated user can create product batch', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'quantity' => 50]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/product-batches', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'initial_quantity' => 100,
            'unit_cost' => 500,
            'product_cost' => 50000,
            'freight_cost' => 5000,
            'customs_duty' => 3000,
            'clearing_fee' => 2000,
            'other_costs' => 1000,
            'purchase_date' => now()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['initial_quantity' => '100.000'])
        ->assertJsonFragment(['total_landed_cost' => '61000.00']);

    expect(ProductBatch::count())->toBe(1);

    $product->refresh();
    expect($product->quantity)->toBe('150.000'); // 50 + 100
});

test('product batch auto-generates batch number', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/product-batches', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'initial_quantity' => 100,
            'unit_cost' => 500,
            'purchase_date' => now()->toDateString(),
        ]);

    $response->assertCreated();
    $batch = ProductBatch::first();
    expect($batch->batch_number)->toContain('BATCH-'.now()->format('Ymd'));
});

test('total landed cost is auto-calculated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/product-batches', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'initial_quantity' => 100,
            'unit_cost' => 100,
            'product_cost' => 10000,
            'freight_cost' => 1000,
            'customs_duty' => 500,
            'purchase_date' => now()->toDateString(),
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['total_landed_cost' => '11500.00']);
});

// Read Tests
test('authenticated user can view product batch details', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/product-batches/{$batch->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $batch->id]);
});

test('user cannot view product batch from another shop', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $product = Product::factory()->create(['shop_id' => $otherShop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $otherShop->id]);
    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/product-batches/{$batch->id}");

    $response->assertNotFound();
});

// Update Tests
test('authenticated user can update product batch costs', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
        'product_cost' => 10000,
        'freight_cost' => 0,
        'customs_duty' => 0,
        'clearing_fee' => 0,
        'other_costs' => 0,
        'total_landed_cost' => 10000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/product-batches/{$batch->id}", [
            'freight_cost' => 2000,
            'customs_duty' => 1000,
        ]);

    $response->assertOk()
        ->assertJsonFragment(['total_landed_cost' => '13000.00']);
});

// Filtering Tests
test('can filter product batches by product', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product1 = Product::factory()->create(['shop_id' => $shop->id]);
    $product2 = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    ProductBatch::factory()->count(2)->create(['product_id' => $product1->id, 'supplier_id' => $supplier->id]);
    ProductBatch::factory()->create(['product_id' => $product2->id, 'supplier_id' => $supplier->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/product-batches?product_id={$product1->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter product batches by depletion status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    ProductBatch::factory()->count(2)->create(['product_id' => $product->id, 'supplier_id' => $supplier->id, 'remaining_quantity' => 100, 'is_depleted' => false]);
    ProductBatch::factory()->depleted()->create(['product_id' => $product->id, 'supplier_id' => $supplier->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/product-batches?is_depleted=false');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter product batches by expiry status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    // Expired batch
    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
        'expiry_date' => now()->subDays(10),
    ]);

    // Expiring soon batch
    ProductBatch::factory()->nearExpiry()->create([
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
    ]);

    // Valid batch
    ProductBatch::factory()->create([
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
        'expiry_date' => now()->addMonths(6),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/product-batches?expiry_status=expired');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// Validation Tests
test('product_id is required to create batch', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/product-batches', [
            'initial_quantity' => 100,
            'purchase_date' => now()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);
});

test('initial_quantity is required to create batch', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/product-batches', [
            'product_id' => $product->id,
            'purchase_date' => now()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['initial_quantity']);
});

// Delete Test
test('cannot delete batch with associated sales or stock movements', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
    ]);

    // Create a stock movement associated with this batch
    \App\Models\StockMovement::factory()->create([
        'product_id' => $product->id,
        'batch_id' => $batch->id,
        'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/product-batches/{$batch->id}");

    $response->assertUnprocessable();
});

test('can delete batch without associated records', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $batch = ProductBatch::factory()->create([
        'product_id' => $product->id,
        'supplier_id' => $supplier->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/product-batches/{$batch->id}");

    $response->assertNoContent();
    expect(ProductBatch::count())->toBe(0);
});
