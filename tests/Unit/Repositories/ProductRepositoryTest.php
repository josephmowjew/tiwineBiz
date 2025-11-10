<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->shop = Shop::factory()->create(['owner_id' => $this->user->id]);
    $this->actingAs($this->user, 'sanctum');
    $this->repository = app(ProductRepository::class);
});

test('search_term filters by product name', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Laptop HP ProBook',
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Desktop Computer',
    ]);

    $products = $this->repository->all(['search_term' => 'Laptop']);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('search_term filters by SKU', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'sku' => 'LAP001',
        'name' => 'Laptop',
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'sku' => 'DESK002',
        'name' => 'Desktop',
    ]);

    $products = $this->repository->all(['search_term' => 'LAP001']);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('search_term filters by barcode', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'barcode' => '1234567890',
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'barcode' => '9876543210',
    ]);

    $products = $this->repository->all(['search_term' => '1234567890']);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('search_term filters by description', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'description' => 'High performance gaming laptop',
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'description' => 'Office desktop computer',
    ]);

    $products = $this->repository->all(['search_term' => 'gaming']);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('search parameter also works for backward compatibility', function () {
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Laptop HP',
    ]);

    $products = $this->repository->all(['search' => 'Laptop']);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product->id);
});

test('it filters by category_id', function () {
    $category = Category::factory()->create(['shop_id' => $this->shop->id]);
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'category_id' => $category->id,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'category_id' => null,
    ]);

    $products = $this->repository->all(['category_id' => $category->id]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('it filters by is_active status', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'is_active' => true,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'is_active' => false,
    ]);

    $products = $this->repository->all(['is_active' => true]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('it filters by low_stock status', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'quantity' => 5,
        'min_stock_level' => 10,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'quantity' => 50,
        'min_stock_level' => 10,
    ]);

    $products = $this->repository->all(['low_stock' => true]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('it filters by stock_status in_stock', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'quantity' => 10,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'quantity' => 0,
    ]);

    $products = $this->repository->all(['stock_status' => 'in_stock']);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('it filters by stock_status out_of_stock', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'quantity' => 10,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'quantity' => 0,
    ]);

    $products = $this->repository->all(['stock_status' => 'out_of_stock']);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product2->id);
});

test('it combines search_term with other filters', function () {
    $category = Category::factory()->create(['shop_id' => $this->shop->id]);

    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'HP Laptop ProBook',
        'category_id' => $category->id,
        'is_active' => true,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'HP Laptop EliteBook',
        'category_id' => $category->id,
        'is_active' => false,
    ]);
    $product3 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Dell Laptop',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $products = $this->repository->all([
        'search_term' => 'HP',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('it eager loads relationships when specified', function () {
    $category = Category::factory()->create(['shop_id' => $this->shop->id]);
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'category_id' => $category->id,
    ]);

    $products = $this->repository->all(['with' => 'category,shop']);

    expect($products->first()->relationLoaded('category'))->toBeTrue();
    expect($products->first()->relationLoaded('shop'))->toBeTrue();
});

test('it defaults to loading category and shop relationships', function () {
    $category = Category::factory()->create(['shop_id' => $this->shop->id]);
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'category_id' => $category->id,
    ]);

    $products = $this->repository->all();

    expect($products->first()->relationLoaded('category'))->toBeTrue();
    expect($products->first()->relationLoaded('shop'))->toBeTrue();
});

test('it sorts by specified column and direction', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Zebra Product',
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Apple Product',
    ]);

    $products = $this->repository->all([
        'sort_by' => 'name',
        'sort_direction' => 'asc',
    ]);

    expect($products->first()->id)->toBe($product2->id);
    expect($products->last()->id)->toBe($product1->id);
});
