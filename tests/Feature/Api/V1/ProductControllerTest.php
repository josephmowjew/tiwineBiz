<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

// Product Image Upload Tests
test('user can upload product image', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
        'images' => [],
    ]);

    $file = UploadedFile::fake()->image('product.jpg');

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/images", [
            'image' => $file,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Image uploaded successfully.',
        ])
        ->assertJsonStructure([
            'data' => ['image_url', 'total_images'],
        ]);

    $product->refresh();
    expect($product->images)->toHaveCount(1);
    expect($product->images[0])->toContain('/storage/product-images/');

    Storage::disk('public')->assertExists(str_replace('/storage/', '', $product->images[0]));
});

test('user can upload multiple images to same product', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
        'images' => [],
    ]);

    // Upload first image
    $file1 = UploadedFile::fake()->image('product1.jpg');
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/images", ['image' => $file1])
        ->assertStatus(201);

    // Upload second image
    $file2 = UploadedFile::fake()->image('product2.jpg');
    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/images", ['image' => $file2]);

    $response->assertStatus(201);
    expect($response->json('data.total_images'))->toBe(2);

    $product->refresh();
    expect($product->images)->toHaveCount(2);
});

test('cannot upload image to inaccessible product', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $otherUser->id,
    ]);

    $file = UploadedFile::fake()->image('product.jpg');

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/images", [
            'image' => $file,
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Product not found or you do not have access to it.',
        ]);
});

test('image upload validates file type', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/images", [
            'image' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

test('image upload validates file size', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    // Create a 6MB file (exceeds 5MB limit)
    $file = UploadedFile::fake()->create('product.jpg', 6144);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/images", [
            'image' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

test('user can delete product image', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    // Create product with existing images
    $imagePath1 = UploadedFile::fake()->image('product1.jpg')->store('product-images', 'public');
    $imagePath2 = UploadedFile::fake()->image('product2.jpg')->store('product-images', 'public');

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
        'images' => ['/storage/'.$imagePath1, '/storage/'.$imagePath2],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/products/{$product->id}/images/0");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Image deleted successfully.',
        ]);

    expect($response->json('data.remaining_images'))->toBe(1);

    $product->refresh();
    expect($product->images)->toHaveCount(1);
    expect($product->images[0])->toBe('/storage/'.$imagePath2);

    Storage::disk('public')->assertMissing($imagePath1);
    Storage::disk('public')->assertExists($imagePath2);
});

test('cannot delete image at invalid index', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
        'images' => ['/storage/product-images/image1.jpg'],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/products/{$product->id}/images/5");

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Image not found at the specified index.',
        ]);
});

test('cannot delete image from inaccessible product', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $otherUser->id,
        'images' => ['/storage/product-images/image1.jpg'],
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/products/{$product->id}/images/0");

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Product not found or you do not have access to it.',
        ]);
});

test('deleting image re-indexes array properly', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $imagePath1 = UploadedFile::fake()->image('product1.jpg')->store('product-images', 'public');
    $imagePath2 = UploadedFile::fake()->image('product2.jpg')->store('product-images', 'public');
    $imagePath3 = UploadedFile::fake()->image('product3.jpg')->store('product-images', 'public');

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
        'images' => ['/storage/'.$imagePath1, '/storage/'.$imagePath2, '/storage/'.$imagePath3],
    ]);

    // Delete middle image
    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/products/{$product->id}/images/1");

    $product->refresh();
    expect($product->images)->toHaveCount(2);
    expect($product->images[0])->toBe('/storage/'.$imagePath1);
    expect($product->images[1])->toBe('/storage/'.$imagePath3);
    expect(array_keys($product->images))->toBe([0, 1]); // Keys should be re-indexed
});

// Stock Adjustment Tests

test('user can increase product stock', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
            'type' => 'increase',
            'quantity' => 50,
            'reason' => 'Restocking from supplier',
            'notes' => 'Additional notes',
            'unit_cost' => 10.50,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Stock adjusted successfully.',
            'data' => [
                'product_id' => $product->id,
                'quantity_before' => 100,
                'quantity_after' => 150,
                'adjustment_type' => 'increase',
                'adjustment_quantity' => 50,
            ],
        ])
        ->assertJsonStructure([
            'data' => ['stock_movement_id'],
        ]);

    $product->refresh();
    expect($product->quantity)->toBe('150.000');

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'movement_type' => 'adjustment_increase',
        'quantity' => 50,
        'quantity_before' => 100,
        'quantity_after' => 150,
        'unit_cost' => 10.50,
        'total_cost' => 525.00,
        'reason' => 'Restocking from supplier',
        'reference_type' => 'adjustment',
        'created_by' => $user->id,
    ]);
});

test('user can decrease product stock', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
            'type' => 'decrease',
            'quantity' => 25,
            'reason' => 'Damaged items removed from stock',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Stock adjusted successfully.',
            'data' => [
                'product_id' => $product->id,
                'quantity_before' => 100,
                'quantity_after' => 75,
                'adjustment_type' => 'decrease',
                'adjustment_quantity' => 25,
            ],
        ]);

    $product->refresh();
    expect($product->quantity)->toBe('75.000');

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'movement_type' => 'adjustment_decrease',
        'quantity' => 25,
        'quantity_before' => 100,
        'quantity_after' => 75,
        'reason' => 'Damaged items removed from stock',
    ]);
});

test('cannot decrease stock below zero', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 10,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
            'type' => 'decrease',
            'quantity' => 50,
            'reason' => 'Testing insufficient stock',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Insufficient stock. Current stock: 10',
        ]);

    $product->refresh();
    expect($product->quantity)->toBe('10.000');
});

test('cannot adjust stock for inaccessible product', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
            'type' => 'increase',
            'quantity' => 10,
            'reason' => 'Should fail',
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Product not found or you do not have access to it.',
        ]);
});

test('stock adjustment validates required fields', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'quantity', 'reason']);
});

test('stock adjustment validates adjustment type', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
            'type' => 'invalid',
            'quantity' => 10,
            'reason' => 'Test',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

test('stock adjustment validates quantity is positive', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
            'type' => 'increase',
            'quantity' => 0,
            'reason' => 'Test',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['quantity']);
});

test('stock adjustment creates stock movement record', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 50,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
            'type' => 'increase',
            'quantity' => 25,
            'reason' => 'Stock replenishment',
            'notes' => 'Additional inventory',
        ]);

    $stockMovement = StockMovement::where('product_id', $product->id)->first();

    expect($stockMovement)->not->toBeNull();
    expect($stockMovement->shop_id)->toBe($shop->id);
    expect($stockMovement->movement_type)->toBe('adjustment_increase');
    expect($stockMovement->quantity)->toBe('25.000');
    expect($stockMovement->quantity_before)->toBe('50.000');
    expect($stockMovement->quantity_after)->toBe('75.000');
    expect($stockMovement->reason)->toBe('Stock replenishment');
    expect($stockMovement->notes)->toBe('Additional inventory');
    expect($stockMovement->reference_type)->toBe('adjustment');
    expect($stockMovement->created_by)->toBe($user->id);
});

test('stock adjustment updates last_restocked_at on increase', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'last_restocked_at' => null,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
            'type' => 'increase',
            'quantity' => 50,
            'reason' => 'New stock arrived',
        ]);

    $product->refresh();
    expect($product->last_restocked_at)->not->toBeNull();
});

test('stock adjustment does not update last_restocked_at on decrease', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'last_restocked_at' => null,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/adjust-stock", [
            'type' => 'decrease',
            'quantity' => 10,
            'reason' => 'Removed damaged items',
        ]);

    $product->refresh();
    expect($product->last_restocked_at)->toBeNull();
});
