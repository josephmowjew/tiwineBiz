<?php

use App\Models\EfdTransaction;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;

// Auth Tests
test('unauthenticated user cannot access efd transactions', function () {
    $response = $this->getJson('/api/v1/efd-transactions');
    $response->assertUnauthorized();
});

test('authenticated user can list efd transactions from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    EfdTransaction::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/efd-transactions');

    $response->assertOk()->assertJsonCount(2, 'data');
});

// Create Tests
test('authenticated user can create efd transaction', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/efd-transactions', [
            'shop_id' => $shop->id,
            'efd_device_id' => 'EFD-1234',
            'efd_device_serial' => 'SN-1234567890',
            'sale_id' => $sale->id,
            'fiscal_receipt_number' => '12345678',
            'fiscal_day_counter' => 42,
            'fiscal_signature' => hash('sha256', 'test-signature'),
            'qr_code_data' => hash('sha256', 'qr-data'),
            'verification_url' => 'https://verify.mra.mw/12345678',
            'total_amount' => 50000,
            'vat_amount' => 8250,
            'transmission_status' => 'success',
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['total_amount' => '50000.00'])
        ->assertJsonFragment(['transmission_status' => 'success']);

    expect(EfdTransaction::count())->toBe(1);
});

test('retry_count defaults to 0 for new transactions', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/efd-transactions', [
            'shop_id' => $shop->id,
            'efd_device_id' => 'EFD-5678',
            'efd_device_serial' => 'SN-0987654321',
            'sale_id' => $sale->id,
            'fiscal_receipt_number' => '87654321',
            'fiscal_day_counter' => 100,
            'fiscal_signature' => hash('sha256', 'sig-2'),
            'qr_code_data' => hash('sha256', 'qr-2'),
            'total_amount' => 25000,
            'vat_amount' => 4125,
            'transmission_status' => 'success',
        ]);

    $response->assertCreated();
    $transaction = EfdTransaction::first();
    expect($transaction->retry_count)->toBe(0);
});

test('next_retry_at is auto-set for failed transactions', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/efd-transactions', [
            'shop_id' => $shop->id,
            'efd_device_id' => 'EFD-9999',
            'efd_device_serial' => 'SN-1111111111',
            'sale_id' => $sale->id,
            'fiscal_receipt_number' => '99999999',
            'fiscal_day_counter' => 1,
            'fiscal_signature' => hash('sha256', 'sig-failed'),
            'qr_code_data' => hash('sha256', 'qr-failed'),
            'total_amount' => 10000,
            'vat_amount' => 1650,
            'transmission_status' => 'failed',
            'mra_response_code' => '500',
            'mra_response_message' => 'Connection timeout',
        ]);

    $response->assertCreated();
    $transaction = EfdTransaction::first();
    expect($transaction->next_retry_at)->not->toBeNull();
});

test('next_retry_at uses exponential backoff based on retry count', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    // First retry - 5 minutes
    $response1 = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/efd-transactions', [
            'shop_id' => $shop->id,
            'efd_device_id' => 'EFD-RETRY1',
            'efd_device_serial' => 'SN-2222222222',
            'sale_id' => $sale->id,
            'fiscal_receipt_number' => '11111111',
            'fiscal_day_counter' => 2,
            'fiscal_signature' => hash('sha256', 'sig-retry-1'),
            'qr_code_data' => hash('sha256', 'qr-retry-1'),
            'total_amount' => 5000,
            'vat_amount' => 825,
            'transmission_status' => 'failed',
            'retry_count' => 0,
        ]);

    $response1->assertCreated();
    $transaction1 = EfdTransaction::where('fiscal_receipt_number', '11111111')->first();
    expect($transaction1->next_retry_at)->toBeGreaterThan(now()->addMinutes(4));
    expect($transaction1->next_retry_at)->toBeLessThan(now()->addMinutes(6));

    // Second retry - 15 minutes
    $sale2 = Sale::factory()->create(['shop_id' => $shop->id]);
    $response2 = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/efd-transactions', [
            'shop_id' => $shop->id,
            'efd_device_id' => 'EFD-RETRY2',
            'efd_device_serial' => 'SN-3333333333',
            'sale_id' => $sale2->id,
            'fiscal_receipt_number' => '22222222',
            'fiscal_day_counter' => 3,
            'fiscal_signature' => hash('sha256', 'sig-retry-2'),
            'qr_code_data' => hash('sha256', 'qr-retry-2'),
            'total_amount' => 5000,
            'vat_amount' => 825,
            'transmission_status' => 'failed',
            'retry_count' => 1,
        ]);

    $response2->assertCreated();
    $transaction2 = EfdTransaction::where('fiscal_receipt_number', '22222222')->first();
    expect($transaction2->next_retry_at)->toBeGreaterThan(now()->addMinutes(14));
    expect($transaction2->next_retry_at)->toBeLessThan(now()->addMinutes(16));
});

test('successful transaction has null next_retry_at', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/efd-transactions', [
            'shop_id' => $shop->id,
            'efd_device_id' => 'EFD-SUCCESS',
            'efd_device_serial' => 'SN-4444444444',
            'sale_id' => $sale->id,
            'fiscal_receipt_number' => '33333333',
            'fiscal_day_counter' => 10,
            'fiscal_signature' => hash('sha256', 'sig-success'),
            'qr_code_data' => hash('sha256', 'qr-success'),
            'total_amount' => 15000,
            'vat_amount' => 2475,
            'transmission_status' => 'success',
            'mra_response_code' => '200',
        ]);

    $response->assertCreated();
    $transaction = EfdTransaction::where('fiscal_receipt_number', '33333333')->first();
    expect($transaction->next_retry_at)->toBeNull();
});

// Read Tests
test('authenticated user can view efd transaction details', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);
    $transaction = EfdTransaction::factory()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/efd-transactions/{$transaction->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $transaction->id]);
});

test('user cannot view efd transaction from another shop', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $sale = Sale::factory()->create(['shop_id' => $otherShop->id]);
    $transaction = EfdTransaction::factory()->create([
        'shop_id' => $otherShop->id,
        'sale_id' => $sale->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/efd-transactions/{$transaction->id}");

    $response->assertNotFound();
});

// Filtering Tests
test('can filter efd transactions by efd device', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    EfdTransaction::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'efd_device_id' => 'EFD-DEVICE-1',
    ]);
    EfdTransaction::factory()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'efd_device_id' => 'EFD-DEVICE-2',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/efd-transactions?efd_device_id=EFD-DEVICE-1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter efd transactions by transmission status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    EfdTransaction::factory()->count(2)->successful()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
    ]);
    EfdTransaction::factory()->failed()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/efd-transactions?transmission_status=success');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter efd transactions by sale', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale1 = Sale::factory()->create(['shop_id' => $shop->id]);
    $sale2 = Sale::factory()->create(['shop_id' => $shop->id]);

    EfdTransaction::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale1->id,
    ]);
    EfdTransaction::factory()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale2->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/efd-transactions?sale_id={$sale1->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter efd transactions pending retry', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    // Pending retry
    EfdTransaction::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'transmission_status' => 'failed',
        'next_retry_at' => now()->subMinute(),
    ]);

    // Not ready for retry yet
    EfdTransaction::factory()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'transmission_status' => 'failed',
        'next_retry_at' => now()->addHour(),
    ]);

    // Successful - no retry
    EfdTransaction::factory()->successful()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/efd-transactions?pending_retry=1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter efd transactions with exhausted retries', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    // Exhausted retries
    EfdTransaction::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'transmission_status' => 'failed',
        'retry_count' => 3,
    ]);

    // Still has retries
    EfdTransaction::factory()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'transmission_status' => 'failed',
        'retry_count' => 1,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/efd-transactions?retry_exhausted=1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter efd transactions by date range', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    EfdTransaction::factory()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'transmitted_at' => now()->subDays(10),
    ]);
    EfdTransaction::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'transmitted_at' => now()->subDays(5),
    ]);
    EfdTransaction::factory()->create([
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'transmitted_at' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/efd-transactions?from_date='.now()->subDays(6)->toDateString().'&to_date='.now()->subDays(4)->toDateString());

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

// Validation Tests
test('shop_id is required to create efd transaction', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/efd-transactions', [
            'efd_device_id' => 'EFD-TEST',
            'efd_device_serial' => 'SN-TEST',
            'sale_id' => $sale->id,
            'fiscal_receipt_number' => '99999999',
            'fiscal_day_counter' => 1,
            'fiscal_signature' => hash('sha256', 'test'),
            'qr_code_data' => hash('sha256', 'test'),
            'total_amount' => 1000,
            'vat_amount' => 165,
            'transmission_status' => 'success',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['shop_id']);
});

test('fiscal_signature is required for MRA compliance', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/efd-transactions', [
            'shop_id' => $shop->id,
            'efd_device_id' => 'EFD-TEST',
            'efd_device_serial' => 'SN-TEST',
            'sale_id' => $sale->id,
            'fiscal_receipt_number' => '88888888',
            'fiscal_day_counter' => 1,
            'qr_code_data' => hash('sha256', 'test'),
            'total_amount' => 1000,
            'vat_amount' => 165,
            'transmission_status' => 'success',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_signature']);
});

test('fiscal_day_counter must be at least 1', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/efd-transactions', [
            'shop_id' => $shop->id,
            'efd_device_id' => 'EFD-TEST',
            'efd_device_serial' => 'SN-TEST',
            'sale_id' => $sale->id,
            'fiscal_receipt_number' => '77777777',
            'fiscal_day_counter' => 0,
            'fiscal_signature' => hash('sha256', 'test'),
            'qr_code_data' => hash('sha256', 'test'),
            'total_amount' => 1000,
            'vat_amount' => 165,
            'transmission_status' => 'success',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_day_counter']);
});
