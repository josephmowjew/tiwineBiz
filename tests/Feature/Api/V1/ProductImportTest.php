<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('user can import products from excel file', function () {
    Excel::fake();

    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $file = UploadedFile::fake()->create('products.xlsx', 100);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $response->assertStatus(200);

    Excel::assertImported('products.xlsx');
});

test('user can import products from csv file', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $category = Category::factory()->create(['shop_id' => $shop->id, 'name' => 'Electronics']);
    $supplier = Supplier::factory()->create(['shop_id' => $shop->id, 'name' => 'Tech Suppliers Ltd']);

    // Create CSV content
    $csvContent = "name,selling_price,cost_price,quantity,category,supplier,sku,barcode,unit\n";
    $csvContent .= "Laptop,1500.00,1200.00,10,Electronics,Tech Suppliers Ltd,SKU001,BAR001,piece\n";
    $csvContent .= "Mouse,25.50,20.00,50,Electronics,Tech Suppliers Ltd,SKU002,BAR002,piece\n";

    $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Products imported successfully.',
            'data' => [
                'imported_count' => 2,
                'failed_count' => 0,
            ],
        ]);

    $this->assertDatabaseHas('products', [
        'shop_id' => $shop->id,
        'name' => 'Laptop',
        'selling_price' => 1500.00,
        'cost_price' => 1200.00,
        'quantity' => 10,
        'category_id' => $category->id,
        'sku' => 'SKU001',
        'barcode' => 'BAR001',
    ]);

    $this->assertDatabaseHas('products', [
        'shop_id' => $shop->id,
        'name' => 'Mouse',
        'selling_price' => 25.50,
        'cost_price' => 20.00,
        'quantity' => 50,
    ]);
});

test('import validates file is required', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'shop_id' => $shop->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('import validates file type', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $file = UploadedFile::fake()->create('products.pdf', 100);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('import validates file size', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $file = UploadedFile::fake()->create('products.xlsx', 15000); // 15MB

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('import validates shop access', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $otherUser->id]);

    $csvContent = "name,selling_price\nTest Product,100.00\n";
    $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'You do not have access to this shop.',
        ]);
});

test('import skips rows with validation errors', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    // CSV with one valid row, one invalid row, and one more valid row
    $csvContent = "name,selling_price,quantity\n";
    $csvContent .= "Valid Product,100.00,10\n";
    $csvContent .= "Invalid Product,,20\n";

    $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $response->assertStatus(207)
        ->assertJsonStructure([
            'message',
            'data' => [
                'imported_count',
                'failed_count',
                'failures' => [
                    '*' => ['row', 'attribute', 'errors'],
                ],
            ],
        ]);

    // Verify at least one product was imported and one failed
    expect($response->json('data.failed_count'))->toBe(1);

    // Valid product should be imported
    $this->assertDatabaseHas('products', [
        'shop_id' => $shop->id,
        'name' => 'Valid Product',
    ]);

    // Invalid product should not be imported
    $this->assertDatabaseMissing('products', [
        'shop_id' => $shop->id,
        'name' => 'Invalid Product',
    ]);
});

test('import links products to existing categories by name', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $category = Category::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Beverages',
    ]);

    $csvContent = "name,selling_price,category\n";
    $csvContent .= "Coca Cola,2.50,Beverages\n";

    $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $product = Product::where('name', 'Coca Cola')->first();
    expect($product->category_id)->toBe($category->id);
});

test('import links products to existing suppliers by name', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $supplier = Supplier::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'ABC Distributors',
    ]);

    $csvContent = "name,selling_price,supplier\n";
    $csvContent .= "Test Product,100.00,ABC Distributors\n";

    $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $product = Product::where('name', 'Test Product')->first();
    expect($product->primary_supplier_id)->toBe($supplier->id);
});

test('import sets default values for optional fields', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $csvContent = "name,selling_price\n";
    $csvContent .= "Minimal Product,50.00\n";

    $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $product = Product::where('name', 'Minimal Product')->first();
    expect($product->cost_price)->toBe('0.00');
    expect($product->quantity)->toBe('0.000');
    expect($product->unit)->toBe('piece');
    expect($product->base_currency)->toBe('MWK');
    expect($product->is_active)->toBeTrue();
});

test('import sets created_by and updated_by to current user', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $csvContent = "name,selling_price\n";
    $csvContent .= "Test Product,100.00\n";

    $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/products/import', [
            'file' => $file,
            'shop_id' => $shop->id,
        ]);

    $product = Product::where('name', 'Test Product')->first();
    expect($product->created_by)->toBe($user->id);
    expect($product->updated_by)->toBe($user->id);
});
