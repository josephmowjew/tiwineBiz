<?php

use App\Models\Product;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates a user successfully', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->not->toBeNull()
        ->and($user->password_hash)->not->toBeNull()
        ->and($user->is_active)->toBeTrue();
});

it('hashes password on creation', function () {
    $user = User::factory()->create([
        'password_hash' => Hash::make('test-password'),
    ]);

    expect(Hash::check('test-password', $user->password_hash))->toBeTrue();
});

it('uses password_hash for authentication', function () {
    $user = User::factory()->create([
        'password_hash' => Hash::make('test-password'),
    ]);

    expect($user->getAuthPassword())->toBe($user->password_hash);
});

it('hides sensitive fields when converting to array', function () {
    $user = User::factory()->create();
    $array = $user->toArray();

    expect($array)->not->toHaveKey('password_hash')
        ->and($array)->not->toHaveKey('two_factor_secret')
        ->and($array)->not->toHaveKey('remember_token');
});

test('user can own multiple shops', function () {
    $user = User::factory()->create();
    $shops = Shop::factory()->count(3)->create(['owner_id' => $user->id]);

    expect($user->ownedShops)->toHaveCount(3)
        ->and($user->ownedShops->first())->toBeInstanceOf(Shop::class);
});

test('user can belong to multiple shops', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $cashierRole = Role::where('name', 'cashier')->first();

    $shop->users()->attach($user->id, [
        'role_id' => $cashierRole->id,
        'is_active' => true,
        'joined_at' => now(),
    ]);

    $user->refresh();

    expect($user->shops)->toHaveCount(1)
        ->and($user->shops->first()->id)->toBe($shop->id);
});

test('user has activity logs relationship', function () {
    $user = User::factory()->create();

    expect($user->activityLogs())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('user has sales relationship', function () {
    $user = User::factory()->create();

    expect($user->sales())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('user can have two factor authentication enabled', function () {
    $user = User::factory()->with2FA()->create();

    expect($user->two_factor_enabled)->toBeTrue()
        ->and($user->two_factor_secret)->not->toBeNull();
});

test('user can be locked', function () {
    $user = User::factory()->locked()->create();

    expect($user->locked_until)->not->toBeNull()
        ->and($user->failed_login_attempts)->toBe(5)
        ->and($user->locked_until->isFuture())->toBeTrue();
});

test('user can be active or inactive', function () {
    $activeUser = User::factory()->create(['is_active' => true]);
    $inactiveUser = User::factory()->create(['is_active' => false]);

    expect($activeUser->is_active)->toBeTrue()
        ->and($inactiveUser->is_active)->toBeFalse();
});

test('user has verified email and phone', function () {
    $user = User::factory()->verified()->create();

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->phone_verified_at)->not->toBeNull();
});

test('user can be unverified', function () {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull()
        ->and($user->phone_verified_at)->toBeNull();
});

test('user tracks last login details', function () {
    $user = User::factory()->create([
        'last_login_at' => now(),
        'last_login_ip' => '192.168.1.1',
    ]);

    expect($user->last_login_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($user->last_login_ip)->toBe('192.168.1.1');
});

test('user has created products relationship', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Product::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    expect($user->createdProducts)->toHaveCount(1)
        ->and($user->createdProducts->first())->toBeInstanceOf(Product::class);
});

test('user has stock movements relationship', function () {
    $user = User::factory()->create();

    expect($user->stockMovements())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('user uses UUID as primary key', function () {
    $user = User::factory()->create();

    expect($user->id)->toBeString()
        ->and(strlen($user->id))->toBe(36);
});
