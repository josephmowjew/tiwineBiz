<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access sales summary report', function () {
    $response = $this->getJson('/api/v1/reports/sales/summary');

    $response->assertUnauthorized();
});

test('unauthenticated user cannot access daily sales report', function () {
    $response = $this->getJson('/api/v1/reports/sales/daily');

    $response->assertUnauthorized();
});

test('unauthenticated user cannot access weekly sales report', function () {
    $response = $this->getJson('/api/v1/reports/sales/weekly');

    $response->assertUnauthorized();
});

test('unauthenticated user cannot access monthly sales report', function () {
    $response = $this->getJson('/api/v1/reports/sales/monthly');

    $response->assertUnauthorized();
});

test('unauthenticated user cannot access sales comparison report', function () {
    $response = $this->getJson('/api/v1/reports/sales/comparison');

    $response->assertUnauthorized();
});

test('unauthenticated user cannot access hourly sales report', function () {
    $response = $this->getJson('/api/v1/reports/sales/hourly');

    $response->assertUnauthorized();
});

test('unauthenticated user cannot access top customers report', function () {
    $response = $this->getJson('/api/v1/reports/sales/top-customers');

    $response->assertUnauthorized();
});

test('authenticated user can access sales summary report', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/sales/summary');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data',
            'generated_at',
        ]);
});

test('sales summary report validates date range', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/sales/summary?start_date=2024-01-15&end_date=2024-01-10');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['end_date']);
});

test('sales summary report validates branch_id format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reports/sales/summary?branch_id=invalid-uuid');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['branch_id']);
});
