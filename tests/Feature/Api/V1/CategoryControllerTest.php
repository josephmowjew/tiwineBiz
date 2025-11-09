<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access categories', function () {
    $response = $this->getJson('/api/v1/categories');

    $response->assertUnauthorized();
});

test('authenticated user can list categories from their shops and system categories', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $shopCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Shop Category',
    ]);

    $systemCategory = Category::factory()->create([
        'shop_id' => null,
        'name' => 'System Category',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'shop_id'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonFragment(['name' => 'Shop Category'])
        ->assertJsonFragment(['name' => 'System Category']);
});

test('user can only see categories from their shops', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shop1 = Shop::factory()->create(['owner_id' => $user1->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $category1 = Category::factory()->create([
        'shop_id' => $shop1->id,
        'name' => 'User1 Category',
    ]);

    $category2 = Category::factory()->create([
        'shop_id' => $shop2->id,
        'name' => 'User2 Category',
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'User1 Category'])
        ->assertJsonMissing(['name' => 'User2 Category']);
});

test('user can create a category', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $categoryData = [
        'shop_id' => $shop->id,
        'name' => 'Electronics',
        'description' => 'Electronic products',
        'display_order' => 1,
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/categories', $categoryData);

    $response->assertCreated()
        ->assertJsonFragment(['name' => 'Electronics'])
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'shop_id'],
        ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Electronics',
        'shop_id' => $shop->id,
    ]);
});

test('category creation auto-generates slug', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $categoryData = [
        'shop_id' => $shop->id,
        'name' => 'Electronic Devices',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/categories', $categoryData);

    $response->assertCreated();

    $this->assertDatabaseHas('categories', [
        'name' => 'Electronic Devices',
        'slug' => 'electronic-devices',
    ]);
});

test('user can create hierarchical categories', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $parentCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Electronics',
        'depth' => 0,
        'path' => '/electronics',
    ]);

    $childCategoryData = [
        'shop_id' => $shop->id,
        'name' => 'Mobile Phones',
        'parent_id' => $parentCategory->id,
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/categories', $childCategoryData);

    $response->assertCreated();

    $childCategory = Category::where('name', 'Mobile Phones')->first();

    expect($childCategory->parent_id)->toBe($parentCategory->id)
        ->and($childCategory->depth)->toBe(1);
});

test('user cannot create category for shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $categoryData = [
        'shop_id' => $shop2->id,
        'name' => 'Unauthorized Category',
    ];

    $response = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/categories', $categoryData);

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('user can view a specific category', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $category = Category::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/categories/{$category->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => $category->name])
        ->assertJsonFragment(['id' => $category->id]);
});

test('user can view system categories', function () {
    $user = User::factory()->create();
    $systemCategory = Category::factory()->create([
        'shop_id' => null,
        'name' => 'System Category',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/categories/{$systemCategory->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => 'System Category']);
});

test('user can view category with children', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $parentCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Parent',
    ]);

    $childCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'parent_id' => $parentCategory->id,
        'name' => 'Child',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/categories/{$parentCategory->id}?include=children");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'children' => [
                    '*' => ['id', 'name'],
                ],
            ],
        ]);
});

test('user cannot view category from shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $category = Category::factory()->create(['shop_id' => $shop2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/categories/{$category->id}");

    $response->assertNotFound()
        ->assertJsonFragment(['message' => 'Category not found or you do not have access to it.']);
});

test('user can update a category', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $category = Category::factory()->create(['shop_id' => $shop->id]);

    $updateData = [
        'name' => 'Updated Category Name',
        'description' => 'Updated description',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/categories/{$category->id}", $updateData);

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Updated Category Name']);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Category Name',
    ]);
});

test('user cannot update system categories', function () {
    $user = User::factory()->create();
    $systemCategory = Category::factory()->create(['shop_id' => null]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/categories/{$systemCategory->id}", [
            'name' => 'Hacked System Category',
        ]);

    $response->assertNotFound();
});

test('user can deactivate a category', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $category = Category::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/categories/{$category->id}");

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Category deactivated successfully.']);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'is_active' => false,
    ]);
});

test('cannot delete category with subcategories', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $parentCategory = Category::factory()->create(['shop_id' => $shop->id]);
    $childCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'parent_id' => $parentCategory->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/categories/{$parentCategory->id}");

    $response->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Cannot delete category with subcategories. Please delete subcategories first.']);
});

test('cannot delete category with products', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $category = Category::factory()->create(['shop_id' => $shop->id]);

    Product::factory()->create([
        'shop_id' => $shop->id,
        'category_id' => $category->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/categories/{$category->id}");

    $response->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Cannot delete category with products. Please reassign products first.']);
});

test('categories can be filtered by parent', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $parentCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'parent_id' => null,
        'name' => 'Parent',
    ]);

    $childCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'parent_id' => $parentCategory->id,
        'name' => 'Child',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/categories?parent_id={$parentCategory->id}");

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Child'])
        ->assertJsonMissing(['name' => 'Parent']);
});

test('categories can be filtered to show only root categories', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $rootCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'parent_id' => null,
        'name' => 'Root',
    ]);

    $childCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'parent_id' => $rootCategory->id,
        'name' => 'Child',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/categories?parent_id=null');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Root'])
        ->assertJsonMissing(['name' => 'Child']);
});

test('categories can be filtered to show only system categories', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $shopCategory = Category::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Shop Category',
    ]);

    $systemCategory = Category::factory()->create([
        'shop_id' => null,
        'name' => 'System Category',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/categories?system_only=true');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'System Category'])
        ->assertJsonMissing(['name' => 'Shop Category']);
});

test('categories can be searched by name', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $category1 = Category::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Electronics',
    ]);

    $category2 = Category::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Clothing',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/categories?search=Electronics');

    $response->assertOk()
        ->assertJsonFragment(['name' => 'Electronics'])
        ->assertJsonMissing(['name' => 'Clothing']);
});

test('categories are sorted by display order by default', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $category1 = Category::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'Third',
        'display_order' => 3,
    ]);

    $category2 = Category::factory()->create([
        'shop_id' => $shop->id,
        'name' => 'First',
        'display_order' => 1,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/categories');

    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['name'])->toBe('First');
});

test('categories list is paginated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Category::factory()->count(60)->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.per_page', 50);
});
