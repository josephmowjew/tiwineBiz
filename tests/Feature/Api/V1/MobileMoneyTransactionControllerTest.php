<?php

use App\Models\MobileMoneyTransaction;
use App\Models\Shop;
use App\Models\User;

// Auth Tests
test('unauthenticated user cannot access mobile money transactions', function () {
    $response = $this->getJson('/api/v1/mobile-money-transactions');
    $response->assertUnauthorized();
});

test('authenticated user can list mobile money transactions from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    MobileMoneyTransaction::factory()->count(2)->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/mobile-money-transactions');

    $response->assertOk()->assertJsonCount(2, 'data');
});

// Create Tests
test('authenticated user can create mobile money transaction', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/mobile-money-transactions', [
            'shop_id' => $shop->id,
            'provider' => 'airtel_money',
            'transaction_id' => 'TXN-AIR-12345678',
            'transaction_type' => 'c2b',
            'msisdn' => '+265888123456',
            'sender_name' => 'John Banda',
            'amount' => 5000,
            'currency' => 'MWK',
            'status' => 'successful',
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['amount' => '5000.00'])
        ->assertJsonFragment(['provider' => 'airtel_money']);

    expect(MobileMoneyTransaction::count())->toBe(1);
});

test('confirmed_at is auto-set for successful transactions', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/mobile-money-transactions', [
            'shop_id' => $shop->id,
            'provider' => 'tnm_mpamba',
            'transaction_id' => 'TXN-TNM-87654321',
            'transaction_type' => 'c2b',
            'msisdn' => '+265999654321',
            'amount' => 10000,
            'currency' => 'MWK',
            'status' => 'successful',
        ]);

    $response->assertCreated();
    $transaction = MobileMoneyTransaction::first();
    expect($transaction->confirmed_at)->not->toBeNull();
});

test('pending transaction has null confirmed_at', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/mobile-money-transactions', [
            'shop_id' => $shop->id,
            'provider' => 'airtel_money',
            'transaction_id' => 'TXN-AIR-11111111',
            'transaction_type' => 'c2b',
            'msisdn' => '+265888111111',
            'amount' => 3000,
            'currency' => 'MWK',
            'status' => 'pending',
        ]);

    $response->assertCreated();
    $transaction = MobileMoneyTransaction::first();
    expect($transaction->confirmed_at)->toBeNull();
});

// Read Tests
test('authenticated user can view mobile money transaction details', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $transaction = MobileMoneyTransaction::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/mobile-money-transactions/{$transaction->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $transaction->id]);
});

test('user cannot view mobile money transaction from another shop', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $transaction = MobileMoneyTransaction::factory()->create(['shop_id' => $otherShop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/mobile-money-transactions/{$transaction->id}");

    $response->assertNotFound();
});

// Filtering Tests
test('can filter mobile money transactions by provider', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    MobileMoneyTransaction::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'provider' => 'airtel_money',
    ]);
    MobileMoneyTransaction::factory()->create([
        'shop_id' => $shop->id,
        'provider' => 'tnm_mpamba',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/mobile-money-transactions?provider=airtel_money');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter mobile money transactions by status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    MobileMoneyTransaction::factory()->count(2)->successful()->create(['shop_id' => $shop->id]);
    MobileMoneyTransaction::factory()->pending()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/mobile-money-transactions?status=successful');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter mobile money transactions by transaction type', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    MobileMoneyTransaction::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'transaction_type' => 'c2b',
    ]);
    MobileMoneyTransaction::factory()->create([
        'shop_id' => $shop->id,
        'transaction_type' => 'b2c',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/mobile-money-transactions?transaction_type=c2b');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter mobile money transactions by date range', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    MobileMoneyTransaction::factory()->create([
        'shop_id' => $shop->id,
        'transaction_date' => now()->subDays(10),
    ]);
    MobileMoneyTransaction::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'transaction_date' => now()->subDays(5),
    ]);
    MobileMoneyTransaction::factory()->create([
        'shop_id' => $shop->id,
        'transaction_date' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/mobile-money-transactions?from_date='.now()->subDays(6)->toDateString().'&to_date='.now()->subDays(4)->toDateString());

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

// Validation Tests
test('shop_id is required to create mobile money transaction', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/mobile-money-transactions', [
            'provider' => 'airtel_money',
            'transaction_id' => 'TXN-AIR-99999999',
            'transaction_type' => 'c2b',
            'msisdn' => '+265888999999',
            'amount' => 1000,
            'currency' => 'MWK',
            'status' => 'pending',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['shop_id']);
});

test('msisdn must be in Malawian format', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/mobile-money-transactions', [
            'shop_id' => $shop->id,
            'provider' => 'airtel_money',
            'transaction_id' => 'TXN-AIR-88888888',
            'transaction_type' => 'c2b',
            'msisdn' => '0888123456', // Invalid format
            'amount' => 1000,
            'currency' => 'MWK',
            'status' => 'pending',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['msisdn']);
});

test('amount must be greater than zero', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/mobile-money-transactions', [
            'shop_id' => $shop->id,
            'provider' => 'airtel_money',
            'transaction_id' => 'TXN-AIR-77777777',
            'transaction_type' => 'c2b',
            'msisdn' => '+265888777777',
            'amount' => 0,
            'currency' => 'MWK',
            'status' => 'pending',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});
