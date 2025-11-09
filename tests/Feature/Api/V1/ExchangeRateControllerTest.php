<?php

use App\Models\ExchangeRate;
use App\Models\User;

// Auth Tests
test('unauthenticated user cannot access exchange rates', function () {
    $response = $this->getJson('/api/v1/exchange-rates');
    $response->assertUnauthorized();
});

test('authenticated user can list exchange rates', function () {
    $user = User::factory()->create();
    ExchangeRate::factory()->count(3)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/exchange-rates');

    $response->assertOk()->assertJsonCount(3, 'data');
});

// Create Tests
test('authenticated user can create exchange rate', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/exchange-rates', [
            'base_currency' => 'MWK',
            'target_currency' => 'USD',
            'official_rate' => 1650.50,
            'street_rate' => 1700.00,
            'effective_date' => now()->toDateString(),
            'source' => 'RBM',
        ]);

    $response->assertCreated()
        ->assertJsonFragment(['official_rate' => '1650.5000'])
        ->assertJsonFragment(['street_rate' => '1700.0000']);

    expect(ExchangeRate::count())->toBe(1);
    expect(ExchangeRate::first()->created_by)->toBe($user->id);
});

test('rate_used defaults to official_rate if not provided', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/exchange-rates', [
            'base_currency' => 'MWK',
            'target_currency' => 'USD',
            'official_rate' => 1650.50,
            'effective_date' => now()->toDateString(),
        ]);

    $response->assertCreated();
    $rate = ExchangeRate::first();
    expect((float) $rate->rate_used)->toBe(1650.5);
});

// Read Tests
test('authenticated user can view exchange rate details', function () {
    $user = User::factory()->create();
    $rate = ExchangeRate::factory()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/exchange-rates/{$rate->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $rate->id]);
});

// Latest Rate Test
test('can get latest exchange rate for currency pair', function () {
    $user = User::factory()->create();

    // Create older rate
    ExchangeRate::factory()->create([
        'base_currency' => 'MWK',
        'target_currency' => 'USD',
        'official_rate' => 1600,
        'effective_date' => now()->subDays(5),
    ]);

    // Create newer rate
    ExchangeRate::factory()->create([
        'base_currency' => 'MWK',
        'target_currency' => 'USD',
        'official_rate' => 1650,
        'effective_date' => now()->subDay(),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/exchange-rates/latest?base_currency=MWK&target_currency=USD');

    $response->assertOk()
        ->assertJsonFragment(['official_rate' => '1650.0000']);
});

test('latest rate returns 404 when no rate found', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/exchange-rates/latest?base_currency=MWK&target_currency=EUR');

    $response->assertNotFound();
});

// Filtering Tests
test('can filter exchange rates by target currency', function () {
    $user = User::factory()->create();

    ExchangeRate::factory()->count(2)->create(['target_currency' => 'USD']);
    ExchangeRate::factory()->create(['target_currency' => 'EUR']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/exchange-rates?target_currency=USD');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter exchange rates by date range', function () {
    $user = User::factory()->create();

    ExchangeRate::factory()->create(['effective_date' => now()->subDays(10)]);
    ExchangeRate::factory()->count(2)->create(['effective_date' => now()->subDays(5)]);
    ExchangeRate::factory()->create(['effective_date' => now()]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/exchange-rates?from_date='.now()->subDays(6)->toDateString().'&to_date='.now()->subDays(4)->toDateString());

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter active only exchange rates', function () {
    $user = User::factory()->create();

    // Active rate (valid today)
    ExchangeRate::factory()->create([
        'effective_date' => now()->subDay(),
        'valid_until' => now()->addDays(5),
    ]);

    // Expired rate
    ExchangeRate::factory()->create([
        'effective_date' => now()->subDays(10),
        'valid_until' => now()->subDay(),
    ]);

    // Future rate (not yet effective)
    ExchangeRate::factory()->create([
        'effective_date' => now()->addDay(),
        'valid_until' => now()->addDays(10),
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/exchange-rates?active_only=true');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// Validation Tests
test('base_currency is required to create exchange rate', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/exchange-rates', [
            'target_currency' => 'USD',
            'official_rate' => 1650,
            'effective_date' => now()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['base_currency']);
});

test('official_rate is required to create exchange rate', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/exchange-rates', [
            'base_currency' => 'MWK',
            'target_currency' => 'USD',
            'effective_date' => now()->toDateString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['official_rate']);
});

// Delete Test
test('can delete exchange rate', function () {
    $user = User::factory()->create();
    $rate = ExchangeRate::factory()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/exchange-rates/{$rate->id}");

    $response->assertNoContent();
    expect(ExchangeRate::count())->toBe(0);
});
