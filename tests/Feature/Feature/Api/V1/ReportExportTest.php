<?php

use App\Models\Branch;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->shop = Shop::factory()->create(['owner_id' => $this->user->id]);
    $this->branch = Branch::factory()->create([
        'shop_id' => $this->shop->id,
        'created_by' => $this->user->id,
    ]);

    $this->role = Role::factory()->create(['shop_id' => $this->shop->id]);
    $this->branch->users()->attach($this->user->id, [
        'role_id' => $this->role->id,
        'assigned_by' => $this->user->id,
    ]);
});

test('unauthenticated user cannot export reports', function () {
    $this->getJson('/api/v1/reports/sales/export?format=pdf&type=summary')
        ->assertUnauthorized();
});

test('export requires format parameter', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/reports/sales/export?type=summary');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['format']);
});

test('export requires type parameter', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/reports/sales/export?format=pdf');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

test('export validates format values', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/reports/sales/export?format=invalid&type=summary');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['format']);
});

test('export validates type values', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/reports/sales/export?format=pdf&type=invalid');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

test('can export sales summary as PDF', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->get('/api/v1/reports/sales/export?format=pdf&type=summary');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('can export sales summary as Excel', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->get('/api/v1/reports/sales/export?format=excel&type=summary');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});

test('can export daily sales report', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->get('/api/v1/reports/sales/export?format=pdf&type=daily&date=2025-11-10');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('can export weekly sales report', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->get('/api/v1/reports/sales/export?format=pdf&type=weekly');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('can export monthly sales report', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->get('/api/v1/reports/sales/export?format=excel&type=monthly&month=11&year=2025');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});

test('export respects date range parameters', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->get('/api/v1/reports/sales/export?format=pdf&type=summary&start_date=2025-11-01&end_date=2025-11-10');

    $response->assertOk();
});

test('export validates date range ordering', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/reports/sales/export?format=pdf&type=summary&start_date=2025-11-10&end_date=2025-11-01');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['end_date']);
});

test('export respects branch filter', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->get('/api/v1/reports/sales/export?format=pdf&type=summary&branch_id='.$this->branch->id);

    $response->assertOk();
});

test('export filename includes type and date', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->get('/api/v1/reports/sales/export?format=pdf&type=summary');

    $response->assertOk();

    $disposition = $response->headers->get('content-disposition');
    expect($disposition)->toContain('sales-report-summary');
    expect($disposition)->toContain(now()->format('Y-m-d'));
});
