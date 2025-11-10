<?php

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->shop = Shop::factory()->create(['owner_id' => $this->user->id]);
});

test('unauthenticated user cannot access sync endpoints', function () {
    $this->postJson('/api/v1/sync/push')->assertUnauthorized();
    $this->postJson('/api/v1/sync/pull')->assertUnauthorized();
    $this->getJson('/api/v1/sync/status')->assertUnauthorized();
});

test('authenticated user can get sync status', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/status');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'pending',
                'conflicts',
                'failed',
                'last_sync_at',
                'has_issues',
            ],
        ]);
});

test('sync push validates required fields', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/sync/push', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['device_id', 'changes']);
});

test('sync push validates change structure', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/sync/push', [
            'device_id' => 'test-device-001',
            'changes' => [
                ['invalid' => 'data'],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'changes.0.entity_type',
            'changes.0.entity_id',
            'changes.0.action',
            'changes.0.data',
        ]);
});

test('sync push validates entity type', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/sync/push', [
            'device_id' => 'test-device-001',
            'changes' => [
                [
                    'entity_type' => 'invalid_entity',
                    'entity_id' => '00000000-0000-0000-0000-000000000000',
                    'action' => 'create',
                    'data' => [],
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['changes.0.entity_type']);
});

test('sync push validates action type', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/sync/push', [
            'device_id' => 'test-device-001',
            'changes' => [
                [
                    'entity_type' => 'product',
                    'entity_id' => '00000000-0000-0000-0000-000000000000',
                    'action' => 'invalid_action',
                    'data' => [],
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['changes.0.action']);
});

test('sync pull requires last sync timestamp', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/sync/pull', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['last_sync_timestamp']);
});

test('sync pull returns changes since timestamp', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/sync/pull', [
            'last_sync_timestamp' => now()->subHour()->toIso8601String(),
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'data',
                'timestamp',
                'has_more',
            ],
        ]);
});

test('sync pull can filter by entity types', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/sync/pull', [
            'last_sync_timestamp' => now()->subHour()->toIso8601String(),
            'entity_types' => ['product', 'customer'],
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);
});

test('sync pull validates entity types', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/sync/pull', [
            'last_sync_timestamp' => now()->subHour()->toIso8601String(),
            'entity_types' => ['invalid_type'],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['entity_types.0']);
});

test('authenticated user can get pending sync items', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/pending');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data',
        ]);
});

test('pending sync items can be limited', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/pending?limit=10');

    $response->assertOk();
});

test('authenticated user can get sync conflicts', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/conflicts');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data',
        ]);
});

test('authenticated user can get sync history', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/history');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data',
        ]);
});

test('sync history can filter by status', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/history?status=completed');

    $response->assertOk();
});

test('sync history validates status parameter', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/history?status=invalid');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

test('resolve conflict validates resolution type', function () {
    $queueItemId = '00000000-0000-0000-0000-000000000000';

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/sync/conflicts/{$queueItemId}/resolve", [
            'resolution' => 'invalid',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['resolution']);
});

test('sync endpoints return proper error for user without shop', function () {
    $userWithoutShop = User::factory()->create();

    $response = $this->actingAs($userWithoutShop, 'sanctum')
        ->getJson('/api/v1/sync/status');

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'No shop found for user',
        ]);
});
