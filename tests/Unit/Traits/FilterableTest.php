<?php

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

test('filterEqual filters by exact match', function () {
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

test('filterDateRange filters by date range with both dates', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'created_at' => now()->subDays(5),
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'created_at' => now()->subDays(15),
    ]);
    $product3 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'created_at' => now()->addDays(1),
    ]);

    $products = $this->repository->all([
        'from_date' => now()->subDays(10)->toDateString(),
        'to_date' => now()->toDateString(),
    ]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('filterDateRange filters from date only', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'created_at' => now()->subDays(5),
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'created_at' => now()->subDays(15),
    ]);

    $products = $this->repository->all([
        'from_date' => now()->subDays(10)->toDateString(),
    ]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('filterDateRange filters to date only', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'created_at' => now()->subDays(15),
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'created_at' => now()->addDays(1),
    ]);

    $products = $this->repository->all([
        'to_date' => now()->toDateString(),
    ]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('it filters by price range', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'selling_price' => 50000,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'selling_price' => 150000,
    ]);
    $product3 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'selling_price' => 250000,
    ]);

    $products = $this->repository->all([
        'min_price' => 100000,
        'max_price' => 200000,
    ]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product2->id);
});

test('it handles min price filter only', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'selling_price' => 50000,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'selling_price' => 150000,
    ]);

    $products = $this->repository->all(['min_price' => 100000]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product2->id);
});

test('it handles max price filter only', function () {
    $product1 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'selling_price' => 50000,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'selling_price' => 150000,
    ]);

    $products = $this->repository->all(['max_price' => 100000]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});
