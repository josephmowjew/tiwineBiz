<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can refund a completed sale fully', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'total_sold' => 10,
        'total_revenue' => 15000,
        'cost_price' => 1000,
        'selling_price' => 1500,
        'created_by' => $user->id,
    ]);

    // Create a sale with the product
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'served_by' => $user->id,
        'total_amount' => 15000,
        'payment_status' => 'paid',
        'completed_at' => now(),
    ]);

    $saleItem = SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 10,
        'unit' => 'piece',
        'unit_cost' => 1000,
        'unit_price' => 1500,
        'subtotal' => 15000,
        'total' => 15000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 15000,
            'refund_reason' => 'Customer dissatisfied with product quality',
            'refund_method' => 'cash',
            'notes' => 'Full refund processed',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Sale refunded successfully.',
            'data' => [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'refund_amount' => 15000,
                'refund_method' => 'cash',
            ],
        ]);

    // Verify sale marked as refunded
    $sale->refresh();
    expect($sale->refunded_at)->not->toBeNull();
    expect($sale->refund_amount)->toBe('15000.00');

    // Verify product quantity restored
    $product->refresh();
    expect((float) $product->quantity)->toBe(110.0); // 100 + 10 refunded
    expect((float) $product->total_sold)->toBe(0.0); // 10 - 10 refunded
    expect((float) $product->total_revenue)->toBe(0.0); // 15000 - 15000 refunded
});

test('user can refund a sale partially with specific items', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 90,
        'total_sold' => 10,
        'total_revenue' => 15000,
        'created_by' => $user->id,
    ]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
        'total_amount' => 15000,
        'payment_status' => 'paid',
    ]);

    $saleItem = SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => 'Test Product',
        'quantity' => 10,
        'unit' => 'piece',
        'unit_cost' => 1000,
        'unit_price' => 1500,
        'subtotal' => 15000,
        'total' => 15000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 4500, // Refunding 3 items worth
            'refund_reason' => 'Partial return',
            'refund_method' => 'cash',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 3,
                ],
            ],
        ]);

    $response->assertStatus(200);

    // Verify product quantity partially restored
    $product->refresh();
    expect((float) $product->quantity)->toBe(93.0); // 90 + 3 refunded
    expect((float) $product->total_sold)->toBe(7.0); // 10 - 3 refunded
    expect((float) $product->total_revenue)->toBe(10500.0); // 15000 - 4500 refunded
});

test('cannot refund sale for inaccessible sale', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $otherUser->id,
        'total_amount' => 10000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 10000,
            'refund_reason' => 'Test',
            'refund_method' => 'cash',
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Sale not found or you do not have access to it.',
        ]);
});

test('refund validates required fields', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['refund_amount', 'refund_reason', 'refund_method']);
});

test('refund validates amount is positive', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 0,
            'refund_reason' => 'Test',
            'refund_method' => 'cash',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['refund_amount']);
});

test('refund validates method is valid', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 100,
            'refund_reason' => 'Test',
            'refund_method' => 'invalid_method',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['refund_method']);
});

test('cannot refund cancelled sale', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
        'total_amount' => 10000,
        'cancelled_at' => now(),
        'cancelled_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 10000,
            'refund_reason' => 'Test',
            'refund_method' => 'cash',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Cannot refund a cancelled sale.',
        ]);
});

test('cannot refund already refunded sale', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
        'total_amount' => 10000,
        'refunded_at' => now(),
        'refund_amount' => 10000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 10000,
            'refund_reason' => 'Test',
            'refund_method' => 'cash',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'This sale has already been refunded.',
        ]);
});

test('refund amount cannot exceed total amount', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
        'total_amount' => 10000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 15000,
            'refund_reason' => 'Test',
            'refund_method' => 'cash',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Refund amount cannot exceed the sale total amount.',
        ]);
});

test('partial refund quantity cannot exceed sold quantity', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 90,
        'created_by' => $user->id,
    ]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
        'total_amount' => 15000,
    ]);

    $saleItem = SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => 'Test Product',
        'quantity' => 10,
        'unit' => 'piece',
        'unit_cost' => 1000,
        'unit_price' => 1500,
        'subtotal' => 15000,
        'total' => 15000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 15000,
            'refund_reason' => 'Test',
            'refund_method' => 'cash',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 15, // More than sold (10)
                ],
            ],
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Refund quantity for item Test Product cannot exceed sold quantity of 10.000.',
        ]);
});

test('refund fails if sale item does not belong to sale', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $sale1 = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
        'total_amount' => 15000,
    ]);

    $sale2 = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
        'total_amount' => 10000,
    ]);

    $saleItem = SaleItem::create([
        'sale_id' => $sale2->id,
        'product_id' => $product->id,
        'product_name' => 'Test Product',
        'quantity' => 5,
        'unit' => 'piece',
        'unit_cost' => 1000,
        'unit_price' => 2000,
        'subtotal' => 10000,
        'total' => 10000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale1->id}/refund", [
            'refund_amount' => 10000,
            'refund_reason' => 'Test',
            'refund_method' => 'cash',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'quantity' => 5,
                ],
            ],
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => "Sale item {$saleItem->id} not found in this sale.",
        ]);
});

test('refund includes optional notes', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'created_by' => $user->id,
    ]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
        'total_amount' => 15000,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 10,
        'unit' => 'piece',
        'unit_cost' => 1000,
        'unit_price' => 1500,
        'subtotal' => 15000,
        'total' => 15000,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/sales/{$sale->id}/refund", [
            'refund_amount' => 15000,
            'refund_reason' => 'Defective product',
            'refund_method' => 'cash',
            'notes' => 'Customer provided photographic evidence of defect',
        ]);

    // Verify notes stored in internal_notes as JSON
    $sale->refresh();
    $metadata = json_decode($sale->internal_notes, true);
    expect($metadata['refund_notes'])->toBe('Customer provided photographic evidence of defect');
    expect($metadata['refund_reason'])->toBe('Defective product');
    expect($metadata['refund_method'])->toBe('cash');
});
