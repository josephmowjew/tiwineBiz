<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can transfer stock between branches', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $fromBranch = Branch::factory()->create(['shop_id' => $shop->id, 'name' => 'Main Branch']);
    $toBranch = Branch::factory()->create(['shop_id' => $shop->id, 'name' => 'Secondary Branch']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/transfer-stock", [
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'quantity' => 25,
            'reason' => 'Restocking secondary branch',
            'notes' => 'Monthly stock redistribution',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Stock transferred successfully between branches.',
            'data' => [
                'product_id' => $product->id,
                'from_branch' => [
                    'id' => $fromBranch->id,
                    'name' => 'Main Branch',
                ],
                'to_branch' => [
                    'id' => $toBranch->id,
                    'name' => 'Secondary Branch',
                ],
                'quantity_transferred' => 25,
            ],
        ])
        ->assertJsonStructure([
            'data' => ['transfer_out_movement_id', 'transfer_in_movement_id'],
        ]);

    // Verify transfer_out stock movement created
    $this->assertDatabaseHas('stock_movements', [
        'shop_id' => $shop->id,
        'branch_id' => $fromBranch->id,
        'product_id' => $product->id,
        'movement_type' => 'transfer_out',
        'quantity' => 25,
        'reason' => 'Restocking secondary branch',
        'from_location' => 'Main Branch',
        'to_location' => 'Secondary Branch',
        'created_by' => $user->id,
    ]);

    // Verify transfer_in stock movement created
    $this->assertDatabaseHas('stock_movements', [
        'shop_id' => $shop->id,
        'branch_id' => $toBranch->id,
        'product_id' => $product->id,
        'movement_type' => 'transfer_in',
        'quantity' => 25,
        'reason' => 'Restocking secondary branch',
        'from_location' => 'Main Branch',
        'to_location' => 'Secondary Branch',
        'created_by' => $user->id,
    ]);

    // Verify movements are linked
    $transferOut = StockMovement::where('movement_type', 'transfer_out')
        ->where('product_id', $product->id)
        ->first();
    $transferIn = StockMovement::where('movement_type', 'transfer_in')
        ->where('product_id', $product->id)
        ->first();

    expect($transferIn->reference_id)->toBe($transferOut->id);
    expect($transferIn->reference_type)->toBe('transfer');
});

test('cannot transfer stock for inaccessible product', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $fromBranch = Branch::factory()->create(['shop_id' => $shop->id]);
    $toBranch = Branch::factory()->create(['shop_id' => $shop->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/transfer-stock", [
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'quantity' => 10,
            'reason' => 'Should fail',
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Product not found or you do not have access to it.',
        ]);
});

test('transfer validates required fields', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/transfer-stock", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['from_branch_id', 'to_branch_id', 'quantity', 'reason']);
});

test('transfer validates branches must be different', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $branch = Branch::factory()->create(['shop_id' => $shop->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/transfer-stock", [
            'from_branch_id' => $branch->id,
            'to_branch_id' => $branch->id,
            'quantity' => 10,
            'reason' => 'Same branch',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['to_branch_id']);
});

test('transfer validates quantity is positive', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $fromBranch = Branch::factory()->create(['shop_id' => $shop->id]);
    $toBranch = Branch::factory()->create(['shop_id' => $shop->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/transfer-stock", [
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'quantity' => 0,
            'reason' => 'Zero quantity',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['quantity']);
});

test('transfer fails if source branch does not belong to product shop', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user->id]);
    $fromBranch = Branch::factory()->create(['shop_id' => $shop2->id]); // Different shop
    $toBranch = Branch::factory()->create(['shop_id' => $shop1->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop1->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/transfer-stock", [
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'quantity' => 10,
            'reason' => 'Cross-shop transfer',
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Source branch not found or does not belong to this product\'s shop.',
        ]);
});

test('transfer fails if destination branch does not belong to product shop', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user->id]);
    $fromBranch = Branch::factory()->create(['shop_id' => $shop1->id]);
    $toBranch = Branch::factory()->create(['shop_id' => $shop2->id]); // Different shop
    $product = Product::factory()->create([
        'shop_id' => $shop1->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/transfer-stock", [
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'quantity' => 10,
            'reason' => 'Cross-shop transfer',
        ]);

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'Destination branch not found or does not belong to this product\'s shop.',
        ]);
});

test('transfer creates two linked stock movements', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $fromBranch = Branch::factory()->create(['shop_id' => $shop->id]);
    $toBranch = Branch::factory()->create(['shop_id' => $shop->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'quantity' => 100,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/transfer-stock", [
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'quantity' => 25,
            'reason' => 'Stock redistribution',
        ]);

    // Count stock movements
    $movementsCount = StockMovement::where('product_id', $product->id)->count();
    expect($movementsCount)->toBe(2);

    // Verify both movements exist
    $transferOut = StockMovement::where('product_id', $product->id)
        ->where('movement_type', 'transfer_out')
        ->first();

    $transferIn = StockMovement::where('product_id', $product->id)
        ->where('movement_type', 'transfer_in')
        ->first();

    expect($transferOut)->not->toBeNull();
    expect($transferIn)->not->toBeNull();

    // Verify linking
    expect($transferIn->reference_id)->toBe($transferOut->id);
    expect($transferOut->branch_id)->toBe($fromBranch->id);
    expect($transferIn->branch_id)->toBe($toBranch->id);
});

test('transfer includes optional notes', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $fromBranch = Branch::factory()->create(['shop_id' => $shop->id]);
    $toBranch = Branch::factory()->create(['shop_id' => $shop->id]);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/products/{$product->id}/transfer-stock", [
            'from_branch_id' => $fromBranch->id,
            'to_branch_id' => $toBranch->id,
            'quantity' => 10,
            'reason' => 'Routine transfer',
            'notes' => 'Additional details about the transfer',
        ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'movement_type' => 'transfer_out',
        'notes' => 'Additional details about the transfer',
    ]);

    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'movement_type' => 'transfer_in',
        'notes' => 'Additional details about the transfer',
    ]);
});
