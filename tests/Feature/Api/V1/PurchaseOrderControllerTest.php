<?php

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;

// Auth Tests
test('unauthenticated user cannot access purchase orders', function () {
    $response = $this->getJson('/api/v1/purchase-orders');
    $response->assertUnauthorized();
});

test('authenticated user can list purchase orders from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    PurchaseOrder::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'supplier_id' => $supplier->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/purchase-orders');

    $response->assertOk()->assertJsonCount(2, 'data');
});

// Create Tests
test('authenticated user can create purchase order with items', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/purchase-orders', [
            'shop_id' => $shop->id,
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity_ordered' => 100,
                    'unit' => 'piece',
                    'unit_price' => 1000,
                    'subtotal' => 100000,
                ],
            ],
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['subtotal' => '100000.00'])
        ->assertJsonFragment(['status' => 'draft']);

    expect(PurchaseOrder::count())->toBe(1);
    expect(PurchaseOrder::first()->items()->count())->toBe(1);
});

test('purchase order auto-generates PO number', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/purchase-orders', [
            'shop_id' => $shop->id,
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'product_name' => 'Test', 'quantity_ordered' => 10, 'unit' => 'piece', 'unit_price' => 100, 'subtotal' => 1000]],
        ]);

    $response->assertCreated();
    $po = PurchaseOrder::first();
    expect($po->po_number)->toContain('PO-'.now()->format('Ymd'));
});

// Read Tests
test('authenticated user can view purchase order details', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'shop_id' => $shop->id,
        'supplier_id' => $supplier->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/purchase-orders/{$purchaseOrder->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $purchaseOrder->id]);
});

test('user cannot view purchase order from another shop', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $otherShop->id]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'shop_id' => $otherShop->id,
        'supplier_id' => $supplier->id,
        'created_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/purchase-orders/{$purchaseOrder->id}");

    $response->assertForbidden();
});

// Update Tests
test('authenticated user can update purchase order status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'shop_id' => $shop->id,
        'supplier_id' => $supplier->id,
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'status' => 'sent',
        ]);

    $response->assertOk()
        ->assertJsonFragment(['status' => 'sent']);

    $purchaseOrder->refresh();
    expect($purchaseOrder->sent_at)->not->toBeNull();
});

test('total recalculates when costs are updated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'shop_id' => $shop->id,
        'supplier_id' => $supplier->id,
        'subtotal' => 100000,
        'tax_amount' => 0,
        'freight_cost' => 0,
        'insurance_cost' => 0,
        'customs_duty' => 0,
        'clearing_fee' => 0,
        'transport_cost' => 0,
        'other_charges' => 0,
        'total_amount' => 100000,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/purchase-orders/{$purchaseOrder->id}", [
            'freight_cost' => 5000,
            'customs_duty' => 3000,
        ]);

    $response->assertOk()
        ->assertJsonFragment(['total_amount' => '108000.00']);
});

// Validation Tests
test('shop_id is required to create purchase order', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'items' => [['product_id' => fake()->uuid(), 'product_name' => 'Test', 'quantity_ordered' => 10, 'unit' => 'piece', 'unit_price' => 100, 'subtotal' => 1000]],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['shop_id']);
});

test('items are required to create purchase order', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/purchase-orders', [
            'shop_id' => $shop->id,
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['items']);
});

// Filtering Tests
test('can filter purchase orders by status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);

    PurchaseOrder::factory()->count(2)->create(['shop_id' => $shop->id, 'supplier_id' => $supplier->id, 'status' => 'draft', 'created_by' => $user->id]);
    PurchaseOrder::factory()->create(['shop_id' => $shop->id, 'supplier_id' => $supplier->id, 'status' => 'sent', 'created_by' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/purchase-orders?status=draft');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter purchase orders by supplier', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier1 = Supplier::factory()->create(['shop_id' => $shop->id]);
    $supplier2 = Supplier::factory()->create(['shop_id' => $shop->id]);

    PurchaseOrder::factory()->count(2)->create(['shop_id' => $shop->id, 'supplier_id' => $supplier1->id, 'created_by' => $user->id]);
    PurchaseOrder::factory()->create(['shop_id' => $shop->id, 'supplier_id' => $supplier2->id, 'created_by' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/purchase-orders?supplier_id={$supplier1->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

// Delete/Cancel Test
test('can cancel draft or sent purchase order', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'shop_id' => $shop->id,
        'supplier_id' => $supplier->id,
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}");

    $response->assertNoContent();

    $purchaseOrder->refresh();
    expect($purchaseOrder->status)->toBe('cancelled');
});

test('cannot cancel confirmed purchase order', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id]);
    $purchaseOrder = PurchaseOrder::factory()->create([
        'shop_id' => $shop->id,
        'supplier_id' => $supplier->id,
        'status' => 'confirmed',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/purchase-orders/{$purchaseOrder->id}");

    $response->assertUnprocessable();
});
