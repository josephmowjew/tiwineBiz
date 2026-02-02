<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a product successfully', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name)->not->toBeNull()
        ->and($product->shop_id)->toBe($shop->id)
        ->and($product->is_active)->toBeTrue();
});

test('product has shop relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($product->shop)->toBeInstanceOf(Shop::class)
        ->and($product->shop->id)->toBe($shop->id);
});

test('product has category relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $category = Category::factory()->create(['shop_id' => $shop->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'category_id' => $category->id,
        'created_by' => $owner->id,
    ]);

    expect($product->category)->toBeInstanceOf(Category::class)
        ->and($product->category->id)->toBe($category->id);
});

test('product has primary supplier relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($product->primarySupplier())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

test('product has created by relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($product->createdBy)->toBeInstanceOf(User::class)
        ->and($product->createdBy->id)->toBe($owner->id);
});

test('product has updated by relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
        'updated_by' => $owner->id,
    ]);

    expect($product->updatedBy)->toBeInstanceOf(User::class)
        ->and($product->updatedBy->id)->toBe($owner->id);
});

test('product has pricing fields', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'cost_price' => 1000.00,
        'selling_price' => 1500.00,
        'created_by' => $owner->id,
    ]);

    expect($product->cost_price)->toBe('1000.00')
        ->and($product->selling_price)->toBe('1500.00');
});

test('product can calculate minimum price from landing cost', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    // landing_cost is the TOTAL cost (purchase price + shipping + customs + MRA)
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'cost_price' => 1000.00,  // Original purchase price
        'landing_cost' => 1200.00,  // Total cost to get to Malawi
        'created_by' => $owner->id,
    ]);

    expect($product->calculateMinimumPrice())->toBe(1200.0);
});

test('product tracks stock quantity', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 50.500,
        'min_stock_level' => 10.000,
        'max_stock_level' => 100.000,
        'reorder_point' => 15.000,
        'created_by' => $owner->id,
    ]);

    expect($product->quantity)->toBe('50.500')
        ->and($product->min_stock_level)->toBe('10.000')
        ->and($product->max_stock_level)->toBe('100.000')
        ->and($product->reorder_point)->toBe('15.000');
});

test('product attributes are stored as JSON', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $attributes = ['color' => 'red', 'size' => 'large'];

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'attributes' => $attributes,
        'created_by' => $owner->id,
    ]);

    expect($product->attributes)->toBeArray()
        ->and($product->attributes)->toBe($attributes);
});

test('product images are stored as JSON', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $images = ['image1.jpg', 'image2.jpg'];

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'images' => $images,
        'created_by' => $owner->id,
    ]);

    expect($product->images)->toBeArray()
        ->and($product->images)->toBe($images);
});

test('product supports soft deletes', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    $productId = $product->id;
    $product->delete();

    expect(Product::find($productId))->toBeNull()
        ->and(Product::withTrashed()->find($productId))->not->toBeNull()
        ->and(Product::withTrashed()->find($productId)->trashed())->toBeTrue();
});

test('product can be active or discontinued', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $activeProduct = Product::factory()->active()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    $discontinuedProduct = Product::factory()->create([
        'shop_id' => $shop->id,
        'is_active' => false,
        'discontinued_at' => now(),
        'created_by' => $owner->id,
    ]);

    expect($activeProduct->is_active)->toBeTrue()
        ->and($activeProduct->discontinued_at)->toBeNull()
        ->and($discontinuedProduct->is_active)->toBeFalse()
        ->and($discontinuedProduct->discontinued_at)->not->toBeNull();
});

test('product can have low stock', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->lowStock()->create([
        'shop_id' => $shop->id,
        'min_stock_level' => 10.000,
        'created_by' => $owner->id,
    ]);

    expect($product->quantity)->toBeLessThan(10);
});

test('product can be out of stock', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->outOfStock()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($product->quantity)->toBe('0.000');
});

test('product can track batches', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->withBatch()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($product->track_batches)->toBeTrue();
});

test('product has VAT fields', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'is_vat_applicable' => true,
        'vat_rate' => 16.5,
        'tax_category' => 'standard',
        'created_by' => $owner->id,
    ]);

    expect($product->is_vat_applicable)->toBeTrue()
        ->and($product->vat_rate)->toBe('16.50')
        ->and($product->tax_category)->toBe('standard');
});

test('product tracks sales statistics', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'total_sold' => 100.000,
        'total_revenue' => 150000.00,
        'last_sold_at' => now(),
        'created_by' => $owner->id,
    ]);

    expect($product->total_sold)->toBe('100.000')
        ->and($product->total_revenue)->toBe('150000.00')
        ->and($product->last_sold_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('product uses UUID as primary key', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($product->id)->toBeString()
        ->and(strlen($product->id))->toBe(36);
});

test('product has storage location fields', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'storage_location' => 'Warehouse A',
        'shelf' => 'S-01',
        'bin' => 'B-05',
        'created_by' => $owner->id,
    ]);

    expect($product->storage_location)->toBe('Warehouse A')
        ->and($product->shelf)->toBe('S-01')
        ->and($product->bin)->toBe('B-05');
});
