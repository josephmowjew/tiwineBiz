<?php

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a sale successfully', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'served_by' => $owner->id,
    ]);

    expect($sale)->toBeInstanceOf(Sale::class)
        ->and($sale->shop_id)->toBe($shop->id)
        ->and($sale->customer_id)->toBe($customer->id)
        ->and($sale->sale_number)->not->toBeNull();
});

test('sale has shop relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    expect($sale->shop)->toBeInstanceOf(Shop::class)
        ->and($sale->shop->id)->toBe($shop->id);
});

test('sale has customer relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $owner->id,
    ]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'served_by' => $owner->id,
    ]);

    expect($sale->customer)->toBeInstanceOf(Customer::class)
        ->and($sale->customer->id)->toBe($customer->id);
});

test('sale has served by relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    expect($sale->servedBy)->toBeInstanceOf(User::class)
        ->and($sale->servedBy->id)->toBe($owner->id);
});

test('sale has items relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    expect($sale->items())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('sale has payments relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    expect($sale->payments())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('sale calculates subtotal and total correctly', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'subtotal' => 10000.00,
        'discount_amount' => 1000.00,
        'tax_amount' => 1485.00,
        'total_amount' => 10485.00,
        'served_by' => $owner->id,
    ]);

    expect($sale->subtotal)->toBe('10000.00')
        ->and($sale->discount_amount)->toBe('1000.00')
        ->and($sale->tax_amount)->toBe('1485.00')
        ->and($sale->total_amount)->toBe('10485.00');
});

test('sale tracks balance correctly', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'total_amount' => 10000.00,
        'amount_paid' => 6000.00,
        'balance' => 4000.00,
        'served_by' => $owner->id,
    ]);

    expect($sale->total_amount)->toBe('10000.00')
        ->and($sale->amount_paid)->toBe('6000.00')
        ->and($sale->balance)->toBe('4000.00');
});

test('sale has payment status', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $paidSale = Sale::factory()->paid()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    $partialSale = Sale::factory()->partial()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    $pendingSale = Sale::factory()->pending()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    expect($paidSale->payment_status)->toBe('paid')
        ->and($paidSale->balance)->toBe('0.00')
        ->and($partialSale->payment_status)->toBe('partial')
        ->and($partialSale->balance)->toBeGreaterThan(0)
        ->and($pendingSale->payment_status)->toBe('pending')
        ->and($pendingSale->amount_paid)->toBe('0.00');
});

test('sale payment methods are stored as JSON', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $paymentMethods = [
        ['method' => 'cash', 'amount' => 5000.00],
        ['method' => 'airtel_money', 'amount' => 3000.00, 'reference' => 'TXN-12345'],
    ];

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'payment_methods' => $paymentMethods,
        'served_by' => $owner->id,
    ]);

    expect($sale->payment_methods)->toBeArray()
        ->and($sale->payment_methods)->toHaveCount(2);
});

test('sale can be fiscalized', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->fiscalized()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    expect($sale->is_fiscalized)->toBeTrue()
        ->and($sale->efd_device_id)->not->toBeNull()
        ->and($sale->efd_receipt_number)->not->toBeNull()
        ->and($sale->efd_qr_code)->not->toBeNull()
        ->and($sale->efd_fiscal_signature)->not->toBeNull();
});

test('sale has EFD fields', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'is_fiscalized' => true,
        'efd_device_id' => 'EFD-1234',
        'efd_receipt_number' => '12345678',
        'efd_transmitted_at' => now(),
        'served_by' => $owner->id,
    ]);

    expect($sale->is_fiscalized)->toBeTrue()
        ->and($sale->efd_device_id)->toBe('EFD-1234')
        ->and($sale->efd_receipt_number)->toBe('12345678')
        ->and($sale->efd_transmitted_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('sale can be cancelled', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    $sale->update([
        'cancelled_at' => now(),
        'cancelled_by' => $owner->id,
        'cancellation_reason' => 'Customer request',
    ]);

    expect($sale->cancelled_at)->not->toBeNull()
        ->and($sale->cancelled_by)->toBe($owner->id)
        ->and($sale->cancellation_reason)->toBe('Customer request');
});

test('sale has cancelled by relationship', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
        'cancelled_at' => now(),
        'cancelled_by' => $owner->id,
    ]);

    expect($sale->cancelledBy)->toBeInstanceOf(User::class)
        ->and($sale->cancelledBy->id)->toBe($owner->id);
});

test('sale can be refunded', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'total_amount' => 10000.00,
        'served_by' => $owner->id,
    ]);

    $sale->update([
        'refunded_at' => now(),
        'refund_amount' => 10000.00,
    ]);

    expect($sale->refunded_at)->not->toBeNull()
        ->and($sale->refund_amount)->toBe('10000.00');
});

test('sale has sale type', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'sale_type' => 'pos',
        'served_by' => $owner->id,
    ]);

    expect($sale->sale_type)->toBe('pos')
        ->and($sale->sale_type)->toBeIn(['pos', 'whatsapp', 'phone_order', 'online']);
});

test('sale has currency and exchange rate', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'currency' => 'USD',
        'exchange_rate' => 1050.5000,
        'amount_in_base_currency' => 10505000.00,
        'served_by' => $owner->id,
    ]);

    expect($sale->currency)->toBe('USD')
        ->and($sale->exchange_rate)->toBe('1050.5000')
        ->and($sale->amount_in_base_currency)->toBe('10505000.00');
});

test('sale has timestamps', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'sale_date' => now(),
        'completed_at' => now(),
        'served_by' => $owner->id,
    ]);

    expect($sale->sale_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($sale->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('sale uses UUID as primary key', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $owner->id,
    ]);

    expect($sale->id)->toBeString()
        ->and(strlen($sale->id))->toBe(36);
});

test('sale has notes fields', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'notes' => 'Customer notes',
        'internal_notes' => 'Internal staff notes',
        'served_by' => $owner->id,
    ]);

    expect($sale->notes)->toBe('Customer notes')
        ->and($sale->internal_notes)->toBe('Internal staff notes');
});

test('sale tracks change given', function () {
    $owner = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $owner->id]);

    $sale = Sale::factory()->paid()->create([
        'shop_id' => $shop->id,
        'total_amount' => 10000.00,
        'amount_paid' => 10000.00,
        'change_given' => 500.00,
        'served_by' => $owner->id,
    ]);

    expect($sale->change_given)->toBe('500.00');
});
