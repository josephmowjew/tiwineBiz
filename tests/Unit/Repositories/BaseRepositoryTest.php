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

test('it detects web device type from request', function () {
    request()->attributes->set('device_type', 'web');

    Product::factory()->count(50)->create(['shop_id' => $this->shop->id]);

    $products = $this->repository->autoPaginate(20);

    // Offset pagination should have these methods
    expect($products)->toHaveMethod('currentPage');
    expect($products)->toHaveMethod('lastPage');
    expect($products)->toHaveMethod('total');
    expect($products->perPage())->toBe(20);
});

test('it detects mobile device type from request', function () {
    request()->attributes->set('device_type', 'mobile');

    Product::factory()->count(50)->create(['shop_id' => $this->shop->id]);

    $products = $this->repository->autoPaginate(20);

    // Cursor pagination should have these methods
    expect($products)->toHaveMethod('nextCursor');
    expect($products)->toHaveMethod('previousCursor');
    expect($products)->not->toHaveMethod('total');
    expect($products->perPage())->toBe(20);
});

test('it defaults to web when no device type set', function () {
    Product::factory()->count(50)->create(['shop_id' => $this->shop->id]);

    $products = $this->repository->autoPaginate(20);

    // Should use offset pagination (web default)
    expect($products)->toHaveMethod('currentPage');
});

test('it enforces max per_page limit for web', function () {
    request()->attributes->set('device_type', 'web');

    Product::factory()->count(150)->create(['shop_id' => $this->shop->id]);

    // Request 200 items, should be capped at 100 (web max)
    $products = $this->repository->autoPaginate(200);

    expect($products->perPage())->toBe(100);
});

test('it enforces max per_page limit for mobile', function () {
    request()->attributes->set('device_type', 'mobile');

    Product::factory()->count(100)->create(['shop_id' => $this->shop->id]);

    // Request 100 items, should be capped at 50 (mobile max)
    $products = $this->repository->autoPaginate(100);

    expect($products->perPage())->toBe(50);
});

test('it uses default per_page for web when not specified', function () {
    request()->attributes->set('device_type', 'web');

    Product::factory()->count(50)->create(['shop_id' => $this->shop->id]);

    $products = $this->repository->autoPaginate();

    expect($products->perPage())->toBe(15); // web default
});

test('it uses default per_page for mobile when not specified', function () {
    request()->attributes->set('device_type', 'mobile');

    Product::factory()->count(50)->create(['shop_id' => $this->shop->id]);

    $products = $this->repository->autoPaginate();

    expect($products->perPage())->toBe(20); // mobile default
});

test('find method returns single model by id', function () {
    $product = Product::factory()->create(['shop_id' => $this->shop->id]);

    $found = $this->repository->find($product->id);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($product->id);
});

test('find method respects shop scope', function () {
    $otherShop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $otherShop->id]);

    $found = $this->repository->find($product->id);

    expect($found)->toBeNull();
});

test('create method creates new model', function () {
    $data = [
        'shop_id' => $this->shop->id,
        'name' => 'Test Product',
        'sku' => 'TEST123',
        'selling_price' => 10000,
        'cost_price' => 8000,
    ];

    $product = $this->repository->create($data);

    expect($product)->toBeInstanceOf(Product::class);
    expect($product->name)->toBe('Test Product');
    expect($product->sku)->toBe('TEST123');
    $this->assertDatabaseHas('products', ['sku' => 'TEST123']);
});

test('update method updates existing model', function () {
    $product = Product::factory()->create([
        'shop_id' => $this->shop->id,
        'name' => 'Old Name',
    ]);

    $updated = $this->repository->update($product->id, ['name' => 'New Name']);

    expect($updated->name)->toBe('New Name');
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'New Name',
    ]);
});

test('delete method soft deletes model', function () {
    $product = Product::factory()->create(['shop_id' => $this->shop->id]);

    $result = $this->repository->delete($product->id);

    expect($result)->toBeTrue();
    $this->assertSoftDeleted('products', ['id' => $product->id]);
});

test('all method returns collection without pagination', function () {
    Product::factory()->count(30)->create(['shop_id' => $this->shop->id]);

    $products = $this->repository->all();

    expect($products)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($products)->toHaveCount(30);
});

test('paginate method always returns offset pagination', function () {
    request()->attributes->set('device_type', 'mobile');

    Product::factory()->count(50)->create(['shop_id' => $this->shop->id]);

    $products = $this->repository->paginate(20);

    // Even on mobile, paginate() should use offset pagination
    expect($products)->toHaveMethod('currentPage');
    expect($products)->toHaveMethod('total');
});

test('cursorPaginate method always returns cursor pagination', function () {
    request()->attributes->set('device_type', 'web');

    Product::factory()->count(50)->create(['shop_id' => $this->shop->id]);

    $products = $this->repository->cursorPaginate(20);

    // Even on web, cursorPaginate() should use cursor pagination
    expect($products)->toHaveMethod('nextCursor');
    expect($products)->not->toHaveMethod('total');
});
