<?php

use App\Models\Branch;
use App\Models\Role;
use App\Models\Sale;
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

test('unauthenticated user cannot access receipt endpoints', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
    ]);

    $this->getJson("/api/v1/receipts/{$sale->id}/view")->assertUnauthorized();
    $this->getJson("/api/v1/receipts/{$sale->id}/download")->assertUnauthorized();
    $this->getJson("/api/v1/receipts/{$sale->id}/html")->assertUnauthorized();
    $this->getJson("/api/v1/receipts/{$sale->id}/print")->assertUnauthorized();
});

test('authenticated user can get receipt html', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
        'served_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/receipts/{$sale->id}/html");

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'html',
                'sale_number',
            ],
        ]);
});

test('receipt html contains sale information', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
        'sale_number' => 'SALE-TEST-001',
        'served_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/receipts/{$sale->id}/html");

    $response->assertOk();

    $html = $response->json('data.html');
    expect($html)->toContain('SALE-TEST-001');
    expect($html)->toContain($this->shop->name);
});

test('receipt supports locale parameter', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
        'served_by' => $this->user->id,
    ]);

    $responseEn = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/receipts/{$sale->id}/html?locale=en");

    $responseNy = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/receipts/{$sale->id}/html?locale=ny");

    $responseEn->assertOk();
    $responseNy->assertOk();

    $htmlEn = $responseEn->json('data.html');
    $htmlNy = $responseNy->json('data.html');

    expect($htmlEn)->toContain('RECEIPT');
    expect($htmlNy)->toContain('RISITI');
});

test('user cannot access receipt from unauthorized branch', function () {
    $otherUser = User::factory()->create();
    $otherShop = Shop::factory()->create(['owner_id' => $otherUser->id]);
    $otherBranch = Branch::factory()->create([
        'shop_id' => $otherShop->id,
        'created_by' => $otherUser->id,
    ]);

    $sale = Sale::factory()->create([
        'shop_id' => $otherShop->id,
        'branch_id' => $otherBranch->id,
        'served_by' => $otherUser->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/receipts/{$sale->id}/html");

    $response->assertNotFound()
        ->assertJson([
            'message' => 'Sale not found or you do not have access.',
        ]);
});

test('receipt view endpoint returns pdf response', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
        'served_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->get("/api/v1/receipts/{$sale->id}/view");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('receipt download endpoint returns pdf with attachment header', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
        'served_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->get("/api/v1/receipts/{$sale->id}/download");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

test('receipt print endpoint returns html response', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
        'served_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->get("/api/v1/receipts/{$sale->id}/print");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/html');
});

test('receipt email endpoint validates email address', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
        'served_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/receipts/{$sale->id}/email", [
            'email' => 'invalid-email',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('receipt email endpoint accepts valid email', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
        'served_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/receipts/{$sale->id}/email", [
            'email' => 'customer@example.com',
        ]);

    $response->assertAccepted()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'email',
                'sale_number',
                'status',
            ],
        ]);
});

test('receipt validates locale parameter', function () {
    $sale = Sale::factory()->create([
        'shop_id' => $this->shop->id,
        'branch_id' => $this->branch->id,
        'served_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/receipts/{$sale->id}/html?locale=invalid");

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['locale']);
});

test('receipt handles non-existent sale id', function () {
    $fakeId = '00000000-0000-0000-0000-000000000000';

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/v1/receipts/{$fakeId}/html");

    $response->assertNotFound();
});
