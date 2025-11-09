<?php

use App\Models\Shop;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;

// Auth Tests
test('unauthenticated user cannot access subscription payments', function () {
    $response = $this->getJson('/api/v1/subscription-payments');
    $response->assertUnauthorized();
});

test('authenticated user can list subscription payments from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    SubscriptionPayment::factory()->count(2)->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscription-payments');

    $response->assertOk()->assertJsonCount(2, 'data');
});

// Create Tests
test('authenticated user can create subscription payment', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscription-payments', [
            'subscription_id' => $subscription->id,
            'shop_id' => $shop->id,
            'amount' => 15000,
            'currency' => 'MWK',
            'payment_method' => 'airtel_money',
            'transaction_reference' => 'TXN-12345678',
            'status' => 'confirmed',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['amount' => '15000.00'])
        ->assertJsonFragment(['status' => 'confirmed']);

    expect(SubscriptionPayment::count())->toBe(1);
});

test('payment number is auto-generated if not provided', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscription-payments', [
            'subscription_id' => $subscription->id,
            'shop_id' => $shop->id,
            'amount' => 15000,
            'currency' => 'MWK',
            'status' => 'pending',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

    $response->assertCreated();
    $payment = SubscriptionPayment::first();

    expect($payment->payment_number)->toStartWith('SUBPAY-');
    expect($payment->payment_number)->toContain(now()->format('Ymd'));
});

test('confirmed payment auto-sets confirmed_at and confirmed_by', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscription-payments', [
            'subscription_id' => $subscription->id,
            'shop_id' => $shop->id,
            'amount' => 15000,
            'currency' => 'MWK',
            'status' => 'confirmed',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

    $response->assertCreated();
    $payment = SubscriptionPayment::first();

    expect($payment->confirmed_at)->not->toBeNull();
    expect($payment->confirmed_by)->toBe($user->id);
});

test('pending payment has null confirmed_at', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscription-payments', [
            'subscription_id' => $subscription->id,
            'shop_id' => $shop->id,
            'amount' => 15000,
            'currency' => 'MWK',
            'status' => 'pending',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

    $response->assertCreated();
    $payment = SubscriptionPayment::first();

    expect($payment->confirmed_at)->toBeNull();
    expect($payment->confirmed_by)->toBeNull();
});

test('payment_date is auto-set if not provided', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscription-payments', [
            'subscription_id' => $subscription->id,
            'shop_id' => $shop->id,
            'amount' => 15000,
            'currency' => 'MWK',
            'status' => 'pending',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

    $response->assertCreated();
    $payment = SubscriptionPayment::first();

    expect($payment->payment_date)->not->toBeNull();
});

// Read Tests
test('authenticated user can view subscription payment details', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);
    $payment = SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/subscription-payments/{$payment->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $payment->id]);
});

test('user cannot view subscription payment from another shop', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $otherShop->id]);
    $payment = SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $otherShop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/subscription-payments/{$payment->id}");

    $response->assertNotFound();
});

// Filtering Tests
test('can filter subscription payments by subscription', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription1 = Subscription::factory()->create(['shop_id' => $shop->id]);
    $subscription2 = Subscription::factory()->create(['shop_id' => $shop->id]);

    SubscriptionPayment::factory()->count(2)->create([
        'subscription_id' => $subscription1->id,
        'shop_id' => $shop->id,
    ]);
    SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription2->id,
        'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/subscription-payments?subscription_id={$subscription1->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter subscription payments by status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    SubscriptionPayment::factory()->count(2)->confirmed()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
    ]);
    SubscriptionPayment::factory()->pending()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscription-payments?status=confirmed');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter subscription payments by payment method', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    SubscriptionPayment::factory()->count(2)->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
        'payment_method' => 'airtel_money',
    ]);
    SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
        'payment_method' => 'bank_transfer',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscription-payments?payment_method=airtel_money');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter subscription payments by date range', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
        'payment_date' => now()->subDays(10),
    ]);
    SubscriptionPayment::factory()->count(2)->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
        'payment_date' => now()->subDays(5),
    ]);
    SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
        'payment_date' => now(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscription-payments?from_date='.now()->subDays(6)->toDateString().'&to_date='.now()->subDays(4)->toDateString());

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter unconfirmed subscription payments', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    // Unconfirmed
    SubscriptionPayment::factory()->count(2)->pending()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
    ]);

    // Confirmed
    SubscriptionPayment::factory()->confirmed()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscription-payments?unconfirmed=1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter subscription payments awaiting confirmation', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    // Awaiting confirmation (pending with payment_date)
    SubscriptionPayment::factory()->count(2)->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
        'status' => 'pending',
        'payment_date' => now(),
    ]);

    // Confirmed
    SubscriptionPayment::factory()->confirmed()->create([
        'subscription_id' => $subscription->id,
        'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscription-payments?awaiting_confirmation=1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

// Validation Tests
test('subscription_id is required to create subscription payment', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscription-payments', [
            'shop_id' => $shop->id,
            'amount' => 15000,
            'currency' => 'MWK',
            'status' => 'pending',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['subscription_id']);
});

test('amount must be greater than zero', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscription-payments', [
            'subscription_id' => $subscription->id,
            'shop_id' => $shop->id,
            'amount' => 0,
            'currency' => 'MWK',
            'status' => 'pending',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('period_end must be after period_start', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscription-payments', [
            'subscription_id' => $subscription->id,
            'shop_id' => $shop->id,
            'amount' => 15000,
            'currency' => 'MWK',
            'status' => 'pending',
            'period_start' => now()->toDateString(),
            'period_end' => now()->subDay()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['period_end']);
});

test('payment_method must be valid', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscription-payments', [
            'subscription_id' => $subscription->id,
            'shop_id' => $shop->id,
            'amount' => 15000,
            'currency' => 'MWK',
            'payment_method' => 'invalid_method',
            'status' => 'pending',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['payment_method']);
});
