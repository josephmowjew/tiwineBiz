<?php

use App\Models\Customer;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a customer successfully', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->name)->not->toBeNull()
        ->and($customer->shop_id)->toBe($shop->id)
        ->and($customer->is_active)->toBeTrue();
});

test('customer has shop relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->shop)->toBeInstanceOf(Shop::class)
        ->and($customer->shop->id)->toBe($shop->id);
});

test('customer has sales relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->sales())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('customer has credits relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->credits())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('customer has payments relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->payments())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('customer has credit limit', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'credit_limit' => 100000.00,
        'created_by' => $owner->id,
    ]);

    expect($customer->credit_limit)->toBe('100000.00');
});

test('customer tracks current balance', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'current_balance' => 25000.00,
        'created_by' => $owner->id,
    ]);

    expect($customer->current_balance)->toBe('25000.00');
});

test('customer has trust level', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'trust_level' => 'trusted',
        'created_by' => $owner->id,
    ]);

    expect($customer->trust_level)->toBe('trusted')
        ->and($customer->trust_level)->toBeIn(['trusted', 'monitor', 'restricted', 'new']);
});

test('customer can be trusted', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->trusted()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->trust_level)->toBe('trusted')
        ->and($customer->payment_behavior_score)->toBeGreaterThanOrEqual(80);
});

test('customer can be blocked', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->blocked()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->is_active)->toBeFalse()
        ->and($customer->blocked_at)->not->toBeNull()
        ->and($customer->block_reason)->not->toBeNull()
        ->and($customer->trust_level)->toBe('restricted');
});

test('customer tracks spending statistics', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'total_spent' => 500000.00,
        'purchase_count' => 25,
        'average_purchase_value' => 20000.00,
        'created_by' => $owner->id,
    ]);

    expect($customer->total_spent)->toBe('500000.00')
        ->and($customer->purchase_count)->toBe(25)
        ->and($customer->average_purchase_value)->toBe('20000.00');
});

test('customer tracks credit statistics', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'total_credit_issued' => 150000.00,
        'total_credit_collected' => 100000.00,
        'created_by' => $owner->id,
    ]);

    expect($customer->total_credit_issued)->toBe('150000.00')
        ->and($customer->total_credit_collected)->toBe('100000.00');
});

test('customer has payment behavior score', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'payment_behavior_score' => 85,
        'created_by' => $owner->id,
    ]);

    expect($customer->payment_behavior_score)->toBe(85)
        ->and($customer->payment_behavior_score)->toBeInt();
});

test('customer has contact information', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'phone' => '+265999123456',
        'email' => 'customer@example.com',
        'whatsapp_number' => '+265999123456',
        'created_by' => $owner->id,
    ]);

    expect($customer->phone)->toBe('+265999123456')
        ->and($customer->email)->toBe('customer@example.com')
        ->and($customer->whatsapp_number)->toBe('+265999123456');
});

test('customer has preferred contact method', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'preferred_contact_method' => 'whatsapp',
        'created_by' => $owner->id,
    ]);

    expect($customer->preferred_contact_method)->toBe('whatsapp')
        ->and($customer->preferred_contact_method)->toBeIn(['phone', 'whatsapp', 'email', 'sms']);
});

test('customer tags are stored as JSON array', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $tags = ['vip', 'wholesale'];

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'tags' => $tags,
        'created_by' => $owner->id,
    ]);

    expect($customer->tags)->toBeArray()
        ->and($customer->tags)->toBe($tags);
});

test('customer has last purchase date', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'last_purchase_date' => now()->subDays(5),
        'created_by' => $owner->id,
    ]);

    expect($customer->last_purchase_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('customer has creator relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->creator)->toBeInstanceOf(User::class)
        ->and($customer->creator->id)->toBe($owner->id);
});

test('customer can have credit limit', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->withCredit()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->credit_limit)->toBeGreaterThan(0)
        ->and($customer->trust_level)->toBe('trusted');
});

test('customer uses UUID as primary key', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->id)->toBeString()
        ->and(strlen($customer->id))->toBe(36);
});

test('customer has customer number', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    expect($customer->customer_number)->toStartWith('CUST-');
});

test('customer has preferred language', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'preferred_language' => 'en',
        'created_by' => $owner->id,
    ]);

    expect($customer->preferred_language)->toBe('en')
        ->and($customer->preferred_language)->toBeIn(['en', 'ny']);
});
