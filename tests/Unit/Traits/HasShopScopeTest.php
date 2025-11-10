<?php

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('it filters results by user accessible shops', function () {
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    // Create shops - user owns shop1, has access to shop2, no access to shop3
    $shop1 = Shop::factory()->create(['owner_id' => $this->user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $shop3 = Shop::factory()->create(['owner_id' => $user3->id]);

    // Attach user to shop2
    $this->user->shops()->attach($shop2->id);

    // Create products in each shop
    $product1 = Product::factory()->create([
        'shop_id' => $shop1->id,
        'created_by' => $this->user->id,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $user2->id,
    ]);
    $product3 = Product::factory()->create([
        'shop_id' => $shop3->id,
        'created_by' => $user3->id,
    ]);

    // Act as user and get products
    $this->actingAs($this->user, 'sanctum');

    $repository = app(ProductRepository::class);
    $products = $repository->all();

    // Should only see products from shop1 and shop2
    expect($products)->toHaveCount(2);
    expect($products->pluck('id')->toArray())->toContain($product1->id, $product2->id);
    expect($products->pluck('id')->toArray())->not->toContain($product3->id);
});

test('it returns empty collection when user has no accessible shops', function () {
    $otherUser = User::factory()->create();

    // Create products in shops user doesn't have access to
    $shop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    Product::factory()->count(3)->create([
        'shop_id' => $shop->id,
        'created_by' => $otherUser->id,
    ]);

    // Act as user with no shops
    $this->actingAs($this->user, 'sanctum');

    $repository = app(ProductRepository::class);
    $products = $repository->all();

    expect($products)->toBeEmpty();
});

test('it includes owned shops in scope', function () {
    $ownedShop = Shop::factory()->create(['owner_id' => $this->user->id]);
    $product = Product::factory()->create([
        'shop_id' => $ownedShop->id,
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user, 'sanctum');

    $repository = app(ProductRepository::class);
    $products = $repository->all();

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product->id);
});

test('it includes shops user is attached to via pivot', function () {
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $this->user->shops()->attach($shop->id);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $otherUser->id,
    ]);

    $this->actingAs($this->user, 'sanctum');

    $repository = app(ProductRepository::class);
    $products = $repository->all();

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product->id);
});

test('it filters by specific shop when shop_id filter provided', function () {
    $shop1 = Shop::factory()->create(['owner_id' => $this->user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $this->user->id]);

    $product1 = Product::factory()->create([
        'shop_id' => $shop1->id,
        'created_by' => $this->user->id,
    ]);
    $product2 = Product::factory()->create([
        'shop_id' => $shop2->id,
        'created_by' => $this->user->id,
    ]);

    $this->actingAs($this->user, 'sanctum');

    $repository = app(ProductRepository::class);
    $products = $repository->all(['shop_id' => $shop1->id]);

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

test('it returns no results when filtering by inaccessible shop', function () {
    $otherUser = User::factory()->create();
    $accessibleShop = Shop::factory()->create(['owner_id' => $this->user->id]);
    $inaccessibleShop = Shop::factory()->create(['owner_id' => $otherUser->id]);

    Product::factory()->create([
        'shop_id' => $accessibleShop->id,
        'created_by' => $this->user->id,
    ]);
    Product::factory()->create([
        'shop_id' => $inaccessibleShop->id,
        'created_by' => $otherUser->id,
    ]);

    $this->actingAs($this->user, 'sanctum');

    $repository = app(ProductRepository::class);
    $products = $repository->all(['shop_id' => $inaccessibleShop->id]);

    expect($products)->toBeEmpty();
});
