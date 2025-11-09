<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a shop successfully', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    expect($shop)->toBeInstanceOf(Shop::class)
        ->and($shop->name)->not->toBeNull()
        ->and($shop->owner_id)->toBe($owner->id)
        ->and($shop->is_active)->toBeTrue();
});

test('shop has owner relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    expect($shop->owner)->toBeInstanceOf(User::class)
        ->and($shop->owner->id)->toBe($owner->id);
});

test('shop can have multiple users', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $users = User::factory()->count(3)->create();
    $cashierRole = Role::where('name', 'cashier')->first();

    foreach ($users as $user) {
        $shop->users()->attach($user->id, [
            'role_id' => $cashierRole->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }

    $shop->refresh();

    expect($shop->users)->toHaveCount(3)
        ->and($shop->users->first())->toBeInstanceOf(User::class);
});

test('shop has products relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    Product::factory()->count(5)->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($shop->products)->toHaveCount(5)
        ->and($shop->products->first())->toBeInstanceOf(Product::class);
});

test('shop has categories relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    Category::factory()->count(3)->create(['shop_id' => $shop->id]);

    expect($shop->categories)->toHaveCount(3)
        ->and($shop->categories->first())->toBeInstanceOf(Category::class);
});

test('shop has customers relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    Customer::factory()->count(10)->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($shop->customers)->toHaveCount(10)
        ->and($shop->customers->first())->toBeInstanceOf(Customer::class);
});

test('shop has suppliers relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    expect($shop->suppliers())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('shop has sales relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    expect($shop->sales())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('shop features are stored as JSON array', function () {
    $owner = User::factory()->create();
    $features = ['basic_pos', 'inventory_management', 'customer_management'];

    $shop = Shop::factory()->create([
        'owner_id' => $owner->id,
        'features' => $features,
    ]);

    expect($shop->features)->toBeArray()
        ->and($shop->features)->toBe($features);
});

test('shop limits are stored as JSON array', function () {
    $owner = User::factory()->create();
    $limits = ['products' => 1000, 'users' => 5, 'monthly_sales' => 5000];

    $shop = Shop::factory()->create([
        'owner_id' => $owner->id,
        'limits' => $limits,
    ]);

    expect($shop->limits)->toBeArray()
        ->and($shop->limits)->toBe($limits);
});

test('shop settings are stored as JSON array', function () {
    $owner = User::factory()->create();
    $settings = [
        'receipt_footer' => 'Thank you!',
        'print_receipt_automatically' => true,
    ];

    $shop = Shop::factory()->create([
        'owner_id' => $owner->id,
        'settings' => $settings,
    ]);

    expect($shop->settings)->toBeArray()
        ->and($shop->settings['receipt_footer'])->toBe('Thank you!');
});

test('shop has subscription fields', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create([
        'owner_id' => $owner->id,
        'subscription_tier' => 'professional',
        'subscription_status' => 'active',
        'subscription_started_at' => now(),
        'subscription_expires_at' => now()->addMonths(1),
    ]);

    expect($shop->subscription_tier)->toBe('professional')
        ->and($shop->subscription_status)->toBe('active')
        ->and($shop->subscription_started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($shop->subscription_expires_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('shop can be active or deactivated', function () {
    $owner = User::factory()->create();
    $activeShop = Shop::factory()->active()->create(['owner_id' => $owner->id]);
    $suspendedShop = Shop::factory()->suspended()->create(['owner_id' => $owner->id]);

    expect($activeShop->is_active)->toBeTrue()
        ->and($activeShop->deactivated_at)->toBeNull()
        ->and($suspendedShop->is_active)->toBeFalse()
        ->and($suspendedShop->deactivated_at)->not->toBeNull();
});

test('shop can be on trial', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->withTrial()->create(['owner_id' => $owner->id]);

    expect($shop->subscription_status)->toBe('trialing')
        ->and($shop->trial_ends_at)->not->toBeNull()
        ->and($shop->trial_ends_at->isFuture())->toBeTrue();
});

test('shop has VAT registration flag', function () {
    $owner = User::factory()->create();
    $vatShop = Shop::factory()->create([
        'owner_id' => $owner->id,
        'is_vat_registered' => true,
        'vrn' => 'VRN-12345678',
    ]);

    expect($vatShop->is_vat_registered)->toBeTrue()
        ->and($vatShop->vrn)->toBe('VRN-12345678');
});

test('shop tracks geographic coordinates', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create([
        'owner_id' => $owner->id,
        'latitude' => -15.7667,
        'longitude' => 35.0000,
    ]);

    expect($shop->latitude)->toBe('-15.76670000')
        ->and($shop->longitude)->toBe('35.00000000');
});

test('shop uses UUID as primary key', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    expect($shop->id)->toBeString()
        ->and(strlen($shop->id))->toBe(36);
});

test('shop has fiscal year start month', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create([
        'owner_id' => $owner->id,
        'fiscal_year_start_month' => 1,
    ]);

    expect($shop->fiscal_year_start_month)->toBe(1)
        ->and($shop->fiscal_year_start_month)->toBeInt();
});

test('shop has default currency', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create([
        'owner_id' => $owner->id,
        'default_currency' => 'MWK',
    ]);

    expect($shop->default_currency)->toBe('MWK');
});
