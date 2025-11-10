<?php

use App\Models\NotificationPreference;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->shop = Shop::factory()->create(['owner_id' => $this->user->id]);
});

test('unauthenticated user cannot access notification endpoints', function () {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
    $this->getJson('/api/v1/notifications/unread-count')->assertUnauthorized();
    $this->postJson('/api/v1/notifications/read-all')->assertUnauthorized();
    $this->getJson('/api/v1/notifications/preferences')->assertUnauthorized();
});

test('authenticated user can get notifications', function () {
    // Create some test notifications
    $this->user->notify(new \App\Notifications\LowStockAlert(
        Product::factory()->create(['shop_id' => $this->shop->id])
    ));

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/notifications');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'data',
                    'read_at',
                    'created_at',
                ],
            ],
        ]);
});

test('can filter notifications to unread only', function () {
    // Create read and unread notifications
    $notification1 = $this->user->notify(new \App\Notifications\LowStockAlert(
        Product::factory()->create(['shop_id' => $this->shop->id])
    ));

    $notification2 = $this->user->notify(new \App\Notifications\LowStockAlert(
        Product::factory()->create(['shop_id' => $this->shop->id])
    ));

    // Mark first as read
    $this->user->notifications()->first()->markAsRead();

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/notifications?unread_only=1');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
});

test('can get unread notification count', function () {
    // Create multiple notifications
    $this->user->notify(new \App\Notifications\LowStockAlert(
        Product::factory()->create(['shop_id' => $this->shop->id])
    ));

    $this->user->notify(new \App\Notifications\LowStockAlert(
        Product::factory()->create(['shop_id' => $this->shop->id])
    ));

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/notifications/unread-count');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'count' => 2,
            ],
        ]);
});

test('can mark notification as read', function () {
    $this->user->notify(new \App\Notifications\LowStockAlert(
        Product::factory()->create(['shop_id' => $this->shop->id])
    ));

    $notification = $this->user->unreadNotifications()->first();

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/notifications/{$notification->id}/read");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

test('cannot mark non-existent notification as read', function () {
    $fakeId = '00000000-0000-0000-0000-000000000000';

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/v1/notifications/{$fakeId}/read");

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Notification not found',
        ]);
});

test('can mark all notifications as read', function () {
    // Create multiple unread notifications
    $this->user->notify(new \App\Notifications\LowStockAlert(
        Product::factory()->create(['shop_id' => $this->shop->id])
    ));

    $this->user->notify(new \App\Notifications\LowStockAlert(
        Product::factory()->create(['shop_id' => $this->shop->id])
    ));

    expect($this->user->unreadNotifications()->count())->toBe(2);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson('/api/v1/notifications/read-all');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

test('can delete notification', function () {
    $this->user->notify(new \App\Notifications\LowStockAlert(
        Product::factory()->create(['shop_id' => $this->shop->id])
    ));

    $notification = $this->user->notifications()->first();

    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/v1/notifications/{$notification->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Notification deleted',
        ]);

    expect($this->user->notifications()->count())->toBe(0);
});

test('cannot delete non-existent notification', function () {
    $fakeId = '00000000-0000-0000-0000-000000000000';

    $response = $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/v1/notifications/{$fakeId}");

    $response->assertNotFound();
});

test('can get notification preferences', function () {
    // Create some preferences
    NotificationPreference::create([
        'user_id' => $this->user->id,
        'notification_type' => 'low_stock',
        'channel' => 'mail',
        'enabled' => true,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/notifications/preferences');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data',
        ]);
});

test('can update notification preferences', function () {
    $preferences = [
        [
            'notification_type' => 'low_stock',
            'channel' => 'mail',
            'enabled' => true,
        ],
        [
            'notification_type' => 'low_stock',
            'channel' => 'sms',
            'enabled' => false,
        ],
        [
            'notification_type' => 'sale_completed',
            'channel' => 'database',
            'enabled' => true,
        ],
    ];

    $response = $this->actingAs($this->user, 'sanctum')
        ->putJson('/api/v1/notifications/preferences', [
            'preferences' => $preferences,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Notification preferences updated',
        ]);

    expect($this->user->notificationPreferences()->count())->toBe(3);
});

test('preference update validates notification type', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->putJson('/api/v1/notifications/preferences', [
            'preferences' => [
                [
                    'notification_type' => 'invalid_type',
                    'channel' => 'mail',
                    'enabled' => true,
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['preferences.0.notification_type']);
});

test('preference update validates channel', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->putJson('/api/v1/notifications/preferences', [
            'preferences' => [
                [
                    'notification_type' => 'low_stock',
                    'channel' => 'invalid_channel',
                    'enabled' => true,
                ],
            ],
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['preferences.0.channel']);
});

test('notification list can be limited', function () {
    // Create many notifications
    for ($i = 0; $i < 10; $i++) {
        $this->user->notify(new \App\Notifications\LowStockAlert(
            Product::factory()->create(['shop_id' => $this->shop->id])
        ));
    }

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/notifications?limit=5');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(5);
});

test('notification list validates limit parameter', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/notifications?limit=200');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['limit']);
});
