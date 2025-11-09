<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access sales', function () {
    $response = $this->getJson('/api/v1/sales');

    $response->assertUnauthorized();
});

test('authenticated user can list sales from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/sales');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'shop_id', 'sale_number'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

test('user can only see sales from shops they have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shop1 = Shop::factory()->create(['owner_id' => $user1->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $sale1 = Sale::factory()->create([
        'shop_id' => $shop1->id,
        'served_by' => $user1->id,
    ]);
    $sale2 = Sale::factory()->create([
        'shop_id' => $shop2->id,
        'served_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/v1/sales');

    $response->assertOk()
        ->assertJsonFragment(['sale_number' => $sale1->sale_number])
        ->assertJsonMissing(['sale_number' => $sale2->sale_number]);
});

test('user can create a sale with items', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'cost_price' => 1000,
        'selling_price' => 1500,
        'created_by' => $user->id,
    ]);

    $saleData = [
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'subtotal' => 15000.00,
        'tax_amount' => 2475.00,
        'total_amount' => 17475.00,
        'amount_paid' => 17475.00,
        'balance' => 0,
        'payment_status' => 'paid',
        'payment_methods' => [['method' => 'cash', 'amount' => 17475.00]],
        'sale_type' => 'pos',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 1500.00,
                'discount_amount' => 0,
                'discount_percentage' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sales', $saleData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'sale_number', 'shop_id'],
        ]);

    $this->assertDatabaseHas('sales', [
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'served_by' => $user->id,
    ]);

    $this->assertDatabaseHas('sale_items', [
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    // Verify product quantity decreased
    $product->refresh();
    expect($product->quantity)->toBe('90.000');
});

test('sale creation updates product statistics', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'total_sold' => 0,
        'total_revenue' => 0,
        'cost_price' => 1000,
        'selling_price' => 1500,
        'created_by' => $user->id,
    ]);

    $saleData = [
        'shop_id' => $shop->id,
        'subtotal' => 15000.00,
        'tax_amount' => 0,
        'total_amount' => 15000.00,
        'amount_paid' => 15000.00,
        'balance' => 0,
        'payment_status' => 'paid',
        'payment_methods' => [['method' => 'cash', 'amount' => 15000.00]],
        'sale_type' => 'pos',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 10,
                'unit_price' => 1500.00,
                'discount_amount' => 0,
            ],
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/sales', $saleData);

    $product->refresh();

    expect($product->total_sold)->toBeGreaterThan(0)
        ->and($product->total_revenue)->toBeGreaterThan(0)
        ->and($product->last_sold_at)->not->toBeNull();
});

test('user cannot create sale for shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user2->id,
    ]);

    $saleData = [
        'shop_id' => $shop2->id,
        'subtotal' => 1500.00,
        'total_amount' => 1500.00,
        'amount_paid' => 1500.00,
        'payment_status' => 'paid',
        'sale_type' => 'pos',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 1500.00,
            ],
        ],
    ];

    $response = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/sales', $saleData);

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('user can view a specific sale', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/sales/{$sale->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $sale->id]);
});

test('user can view sale with included relationships', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/sales/{$sale->id}?include=customer,servedBy");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'customer' => ['id', 'name'],
                'served_by' => ['id', 'name'],
            ],
        ]);
});

test('user cannot view sale from shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop2->id,
        'served_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/sales/{$sale->id}");

    $response->assertNotFound()
        ->assertJsonFragment(['message' => 'Sale not found or you do not have access to it.']);
});

test('sales can be filtered by payment status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $paidSale = Sale::factory()->paid()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $pendingSale = Sale::factory()->pending()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/sales?payment_status=paid');

    $response->assertOk()
        ->assertJsonFragment(['sale_number' => $paidSale->sale_number])
        ->assertJsonMissing(['sale_number' => $pendingSale->sale_number]);
});

test('sales can be filtered by date range', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $oldSale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'sale_date' => now()->subDays(10),
        'served_by' => $user->id,
    ]);

    $recentSale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'sale_date' => now(),
        'served_by' => $user->id,
    ]);

    $fromDate = now()->subDays(2)->format('Y-m-d');
    $toDate = now()->format('Y-m-d');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/sales?from_date={$fromDate}&to_date={$toDate}");

    $response->assertOk()
        ->assertJsonFragment(['sale_number' => $recentSale->sale_number])
        ->assertJsonMissing(['sale_number' => $oldSale->sale_number]);
});

test('sales can be filtered by customer', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer1 = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
    $customer2 = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $sale1 = Sale::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer1->id,
        'served_by' => $user->id,
    ]);
    $sale2 = Sale::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer2->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/sales?customer_id={$customer1->id}");

    $response->assertOk()
        ->assertJsonFragment(['sale_number' => $sale1->sale_number])
        ->assertJsonMissing(['sale_number' => $sale2->sale_number]);
});

test('cancelled sales are excluded by default', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $activeSale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $cancelledSale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'cancelled_at' => now(),
        'cancelled_by' => $user->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/sales');

    $response->assertOk()
        ->assertJsonFragment(['sale_number' => $activeSale->sale_number])
        ->assertJsonMissing(['sale_number' => $cancelledSale->sale_number]);
});

test('user can cancel a sale', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 90,
        'total_sold' => 10,
        'created_by' => $user->id,
    ]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    // Create sale item
    $sale->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'product_sku' => $product->sku,
        'quantity' => 10,
        'unit' => $product->unit,
        'unit_cost' => $product->cost_price,
        'unit_price' => 1500,
        'subtotal' => 15000,
        'total' => 15000,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/sales/{$sale->id}", [
            'reason' => 'Customer request',
        ]);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Sale cancelled successfully.']);

    $this->assertDatabaseHas('sales', [
        'id' => $sale->id,
        'cancelled_by' => $user->id,
    ]);

    // Verify product quantity restored
    $product->refresh();
    expect($product->quantity)->toBe('100.000');
});

test('user cannot cancel already cancelled sale', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'cancelled_at' => now(),
        'cancelled_by' => $user->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/sales/{$sale->id}");

    $response->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Sale is already cancelled.']);
});

test('sales can be searched by sale number', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $sale1 = Sale::factory()->create([
        'shop_id' => $shop->id,
        'sale_number' => 'SALE-ABC123',
        'served_by' => $user->id,
    ]);

    $sale2 = Sale::factory()->create([
        'shop_id' => $shop->id,
        'sale_number' => 'SALE-XYZ789',
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/sales?search=ABC');

    $response->assertOk()
        ->assertJsonFragment(['sale_number' => 'SALE-ABC123'])
        ->assertJsonMissing(['sale_number' => 'SALE-XYZ789']);
});

test('sales list is paginated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Sale::factory()->count(20)->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/sales');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.per_page', 15);
});
