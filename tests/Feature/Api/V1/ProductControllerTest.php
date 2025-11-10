<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access products', function () {
    $response = $this->getJson('/api/v1/products');

    $response->assertUnauthorized();
});

test('authenticated user can list products from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'shop_id'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonFragment(['name' => $product->name]);
});

test('user can only see products from shops they have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shop1 = Shop::factory()->create(['owner_id' => $user1->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $product1 = Product::factory()->create([
        'shop_id' => $shop1->id,
        'created_by' => $user1->id,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/v1/products');

    $response->assertOk()
        ->assertJsonFragment(['name' => $product1->name])
        ->assertJsonMissing(['name' => $product2->name]);
});

test('user can create a product', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $category = Category::factory()->create(['shop_id' => $shop->id]);

    $productData = [
        'shop_id' => $shop->id,
        'name' => 'Test Product',
        'category_id' => $category->id,
        'cost_price' => 1000.00,
        'selling_price' => 1500.00,
        'quantity' => 50,
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products', $productData);

    $response->assertCreated()
        ->assertJsonFragment(['name' => 'Test Product'])
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'shop_id'],
        ]);

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
});

test('product creation validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['shop_id', 'name', 'cost_price', 'selling_price']);
});

test('user cannot create product for shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $productData = [
        'shop_id' => $shop2->id,
        'name' => 'Unauthorized Product',
        'cost_price' => 1000.00,
        'selling_price' => 1500.00,
    ];

    $response = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/products', $productData);

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('user can view a specific product', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/products/{$product->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $product->name])
        ->assertJsonFragment(['id' => $product->id]);
});

test('user can view product with included relationships', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $category = Category::factory()->create(['shop_id' => $shop->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'category_id' => $category->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/products/{$product->id}?include=category,shop");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'category' => ['id', 'name'],
                'shop' => ['id', 'name'],
            ],
        ]);
});

test('user cannot view product from shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/products/{$product->id}");

    $response->assertNotFound()
        ->assertJsonFragment(['message' => 'Product not found or you do not have access to it.']);
});

test('user can update a product', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $updateData = [
        'name' => 'Updated Product Name',
        'selling_price' => 2000.00,
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/products/{$product->id}", $updateData);

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Updated Product Name']);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Product Name',
        'updated_by' => $user->id,
    ]);
});

test('user cannot update product from shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->putJson("/api/v1/products/{$product->id}", [
            'name' => 'Hacked Product',
        ]);

    $response->assertNotFound();
});

test('user can delete a product', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/products/{$product->id}");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Product deleted successfully.']);

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('products can be filtered by category', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $category1 = Category::factory()->create(['shop_id' => $shop->id, 'name' => 'Electronics']);
    $category2 = Category::factory()->create(['shop_id' => $shop->id, 'name' => 'Clothing']);

    $product1 = Product::factory()->create([
        'shop_id' => $shop->id,
        'category_id' => $category1->id,
        'created_by' => $user->id,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $shop->id,
        'category_id' => $category2->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/products?category_id={$category1->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $product1->name])
        ->assertJsonMissing(['name' => $product2->name]);
});

test('products can be filtered by low stock', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $lowStockProduct = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 5,
        'min_stock_level' => 10,
        'created_by' => $user->id,
    ]);

    $normalProduct = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 50,
        'min_stock_level' => 10,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products?low_stock=true');

    $response->assertOk()
        ->assertJsonFragment(['name' => $lowStockProduct->name])
        ->assertJsonMissing(['name' => $normalProduct->name]);
});

test('products can be searched by name, SKU, or barcode', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $product1 = Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Samsung Galaxy Phone',
        'sku' => 'SAM-123',
        'created_by' => $user->id,
    ]);

    $product2 = Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Apple iPhone',
        'sku' => 'APP-456',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products?search=Samsung');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Samsung Galaxy Phone'])
        ->assertJsonMissing(['name' => 'Apple iPhone']);
});

test('products can be filtered by specific shop', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user->id]);

    $product1 = Product::factory()->create([
        'shop_id' => $shop1->id,
        'created_by' => $user->id,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/products?shop_id={$shop1->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $product1->name])
        ->assertJsonMissing(['name' => $product2->name]);
});

test('user cannot filter products by shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/products?shop_id={$shop2->id}");

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('products can be filtered by active status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $activeProduct = Product::factory()->active()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $inactiveProduct = Product::factory()->create([
        'shop_id' => $shop->id,
        'is_active' => false,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products?is_active=true');

    $response->assertOk()
        ->assertJsonFragment(['name' => $activeProduct->name])
        ->assertJsonMissing(['name' => $inactiveProduct->name]);
});

test('products can be sorted', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $productA = Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'A Product',
        'created_by' => $user->id,
    ]);

    $productZ = Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Z Product',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products?sort_by=name&sort_order=asc');

    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['name'])->toBe('A Product');
});

test('products list is paginated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Product::factory()->count(20)->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.per_page', 15);
});

test('products can be searched by search_term in name', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $product1 = Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'HP Laptop ProBook',
        'created_by' => $user->id,
    ]);

    $product2 = Product::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Dell Desktop Computer',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products?search_term=Laptop');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'HP Laptop ProBook'])
        ->assertJsonMissing(['name' => 'Dell Desktop Computer']);
});

test('products can be searched by search_term in SKU', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $product1 = Product::factory()->create([
        'shop_id' => $shop->id,
        'sku' => 'LAP-HP-001',
        'created_by' => $user->id,
    ]);

    $product2 = Product::factory()->create([
        'shop_id' => $shop->id,
        'sku' => 'DESK-DEL-002',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products?search_term=LAP-HP');

    $response->assertOk()
        ->assertJsonFragment(['sku' => 'LAP-HP-001'])
        ->assertJsonMissing(['sku' => 'DESK-DEL-002']);
});

test('products can be searched by search_term in barcode', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $product1 = Product::factory()->create([
        'shop_id' => $shop->id,
        'barcode' => '1234567890123',
        'created_by' => $user->id,
    ]);

    $product2 = Product::factory()->create([
        'shop_id' => $shop->id,
        'barcode' => '9876543210987',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products?search_term=123456');

    $response->assertOk()
        ->assertJsonFragment(['barcode' => '1234567890123'])
        ->assertJsonMissing(['barcode' => '9876543210987']);
});

test('products can be searched by search_term in description', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $product1 = Product::factory()->create([
        'shop_id' => $shop->id,
        'description' => 'High performance gaming laptop with RTX graphics',
        'created_by' => $user->id,
    ]);

    $product2 = Product::factory()->create([
        'shop_id' => $shop->id,
        'description' => 'Office desktop computer for productivity',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products?search_term=gaming');

    $response->assertOk()
        ->assertJsonFragment(['description' => 'High performance gaming laptop with RTX graphics'])
        ->assertJsonMissing(['description' => 'Office desktop computer for productivity']);
});

test('search_term combines with pagination', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Product::factory()->count(30)->create([
        'shop_id' => $shop->id,
        'name' => 'Laptop Model',
        'created_by' => $user->id,
    ]);

    Product::factory()->count(10)->create([
        'shop_id' => $shop->id,
        'name' => 'Desktop Model',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/products?search_term=Laptop&page=1&per_page=10');

    $response->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 30);

    $data = $response->json('data');
    expect($data)->toHaveCount(10);
});

test('web device receives offset pagination', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Product::factory()->count(30)->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0'])
        ->getJson('/api/v1/products?per_page=20');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response->json('meta.current_page'))->toBe(1);
    expect($response->json('meta.total'))->toBe(30);
});

test('mobile device receives cursor pagination', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Product::factory()->count(30)->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)'])
        ->getJson('/api/v1/products?per_page=20');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['per_page', 'next_cursor', 'prev_cursor', 'has_more'],
        ]);

    expect($response->json('meta.has_more'))->toBe(true);
    expect($response->json('meta'))->not->toHaveKey('total');
});
