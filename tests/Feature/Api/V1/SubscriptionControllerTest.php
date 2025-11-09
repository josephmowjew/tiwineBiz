<?php

use App\Models\Shop;
use App\Models\Subscription;
use App\Models\User;

// Auth Tests
test('unauthenticated user cannot access subscriptions', function () {
    $response = $this->getJson('/api/v1/subscriptions');
    $response->assertUnauthorized();
});

test('authenticated user can list subscriptions from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Subscription::factory()->count(2)->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscriptions');

    $response->assertOk()->assertJsonCount(2, 'data');
});

// Create Tests
test('authenticated user can create subscription', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscriptions', [
            'shop_id' => $shop->id,
            'plan' => 'business',
            'billing_cycle' => 'monthly',
            'amount' => 15000,
            'currency' => 'MWK',
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['amount' => '15000.00'])
        ->assertJsonFragment(['plan' => 'business']);

    expect(Subscription::count())->toBe(1);
});

test('subscription auto-sets period dates', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscriptions', [
            'shop_id' => $shop->id,
            'plan' => 'professional',
            'billing_cycle' => 'annual',
            'amount' => 350000,
            'currency' => 'MWK',
        ]);

    $response->assertCreated();
    $subscription = Subscription::first();

    expect($subscription->started_at)->not->toBeNull();
    expect($subscription->current_period_start)->not->toBeNull();
    expect($subscription->current_period_end)->not->toBeNull();
    expect($subscription->current_period_end)->toBeGreaterThan($subscription->current_period_start);
});

test('annual subscription sets period end to 12 months', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscriptions', [
            'shop_id' => $shop->id,
            'plan' => 'enterprise',
            'billing_cycle' => 'annual',
            'amount' => 750000,
            'currency' => 'MWK',
        ]);

    $response->assertCreated();
    $subscription = Subscription::first();

    $expectedEnd = now()->addMonths(12);
    expect($subscription->current_period_end->diffInDays($expectedEnd))->toBeLessThan(1);
});

test('monthly subscription sets period end to 1 month', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscriptions', [
            'shop_id' => $shop->id,
            'plan' => 'business',
            'billing_cycle' => 'monthly',
            'amount' => 15000,
            'currency' => 'MWK',
        ]);

    $response->assertCreated();
    $subscription = Subscription::first();

    $expectedEnd = now()->addMonths(1);
    expect($subscription->current_period_end->diffInDays($expectedEnd))->toBeLessThan(1);
});

// Read Tests
test('authenticated user can view subscription details', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/subscriptions/{$subscription->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $subscription->id]);
});

test('user cannot view subscription from another shop', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $otherShop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/subscriptions/{$subscription->id}");

    $response->assertNotFound();
});

// Update Tests
test('authenticated user can update subscription', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'plan' => 'business',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/subscriptions/{$subscription->id}", [
            'plan' => 'professional',
            'amount' => 35000,
        ]);

    $response->assertOk()
        ->assertJsonFragment(['plan' => 'professional']);

    expect($subscription->fresh()->plan)->toBe('professional');
});

test('cancelling subscription sets cancelled_at', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/subscriptions/{$subscription->id}", [
            'status' => 'cancelled',
            'cancel_reason' => 'Too expensive',
        ]);

    $response->assertOk();
    $subscription->refresh();

    expect($subscription->status)->toBe('cancelled');
    expect($subscription->cancelled_at)->not->toBeNull();
    expect($subscription->cancel_reason)->toBe('Too expensive');
});

test('cancel_at_period_end keeps subscription active until period ends', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/subscriptions/{$subscription->id}", [
            'status' => 'cancelled',
            'cancel_at_period_end' => true,
        ]);

    $response->assertOk();
    $subscription->refresh();

    // Status should stay active when cancel_at_period_end is true
    expect($subscription->status)->toBe('active');
    expect($subscription->cancel_at_period_end)->toBeTrue();
    expect($subscription->cancelled_at)->not->toBeNull();
});

// Delete Tests
test('authenticated user can delete subscription', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/subscriptions/{$subscription->id}");

    $response->assertOk();
    expect(Subscription::count())->toBe(0);
});

test('user cannot delete subscription from another shop', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $subscription = Subscription::factory()->create(['shop_id' => $otherShop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/subscriptions/{$subscription->id}");

    $response->assertNotFound();
    expect(Subscription::count())->toBe(1);
});

// Filtering Tests
test('can filter subscriptions by plan', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Subscription::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'plan' => 'business',
    ]);
    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'plan' => 'professional',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscriptions?plan=business');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter subscriptions by status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Subscription::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'status' => 'active',
    ]);
    Subscription::factory()->cancelled()->create([
        'shop_id' => $shop->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscriptions?status=active');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter subscriptions by billing cycle', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Subscription::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'billing_cycle' => 'monthly',
    ]);
    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'billing_cycle' => 'annual',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscriptions?billing_cycle=monthly');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter subscriptions expiring soon', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    // Expiring soon
    Subscription::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(5),
    ]);

    // Not expiring soon
    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscriptions?expiring_soon=1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter expired subscriptions', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    // Expired
    Subscription::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'status' => 'expired',
        'current_period_end' => now()->subDays(10),
    ]);

    // Active
    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'status' => 'active',
        'current_period_end' => now()->addDays(30),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscriptions?expired=1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter subscriptions with pending cancellation', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    // Pending cancellation
    Subscription::factory()->count(2)->create([
        'shop_id' => $shop->id,
        'cancel_at_period_end' => true,
        'current_period_end' => now()->addDays(10),
    ]);

    // Active without cancellation
    Subscription::factory()->create([
        'shop_id' => $shop->id,
        'cancel_at_period_end' => false,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/subscriptions?pending_cancellation=1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

// Validation Tests
test('shop_id is required to create subscription', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscriptions', [
            'plan' => 'business',
            'billing_cycle' => 'monthly',
            'amount' => 15000,
            'currency' => 'MWK',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['shop_id']);
});

test('plan must be valid', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscriptions', [
            'shop_id' => $shop->id,
            'plan' => 'invalid_plan',
            'billing_cycle' => 'monthly',
            'amount' => 15000,
            'currency' => 'MWK',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['plan']);
});

test('billing_cycle must be valid', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscriptions', [
            'shop_id' => $shop->id,
            'plan' => 'business',
            'billing_cycle' => 'weekly',
            'amount' => 15000,
            'currency' => 'MWK',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['billing_cycle']);
});

test('amount must be non-negative', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/subscriptions', [
            'shop_id' => $shop->id,
            'plan' => 'business',
            'billing_cycle' => 'monthly',
            'amount' => -100,
            'currency' => 'MWK',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});
