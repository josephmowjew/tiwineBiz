<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access payments', function () {
    $response = $this->getJson('/api/v1/payments');

    $response->assertUnauthorized();
});

test('authenticated user can list payments from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
    $payment = Payment::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/payments');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'payment_number', 'amount', 'payment_method'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonFragment(['payment_number' => $payment->payment_number]);
});

test('user can only see payments from shops they have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shop1 = Shop::factory()->create(['owner_id' => $user1->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $payment1 = Payment::factory()->create([
        'shop_id' => $shop1->id,
        'received_by' => $user1->id,
    ]);
    $payment2 = Payment::factory()->create([
        'shop_id' => $shop2->id,
        'received_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/v1/payments');

    $response->assertOk()
        ->assertJsonFragment(['payment_number' => $payment1->payment_number])
        ->assertJsonMissing(['payment_number' => $payment2->payment_number]);
});

test('user can record a cash payment', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $paymentData = [
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'amount' => 50000.00,
        'payment_method' => 'cash',
        'notes' => 'Payment for invoice #123',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', $paymentData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'payment_number', 'amount'],
        ])
        ->assertJsonFragment(['amount' => '50000.00']);

    $this->assertDatabaseHas('payments', [
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'amount' => 50000.00,
        'payment_method' => 'cash',
        'received_by' => $user->id,
    ]);
});

test('user can record a mobile money payment', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $paymentData = [
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'amount' => 25000.00,
        'payment_method' => 'airtel_money',
        'transaction_reference' => 'AM123456789',
        'mobile_money_details' => [
            'phone' => '+265999123456',
            'sender_name' => 'John Banda',
        ],
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', $paymentData);

    $response->assertCreated()
        ->assertJsonFragment(['payment_method' => 'airtel_money'])
        ->assertJsonFragment(['transaction_reference' => 'AM123456789']);

    $this->assertDatabaseHas('payments', [
        'shop_id' => $shop->id,
        'payment_method' => 'airtel_money',
        'transaction_reference' => 'AM123456789',
    ]);
});

test('user can record a cheque payment', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $paymentData = [
        'shop_id' => $shop->id,
        'amount' => 100000.00,
        'payment_method' => 'cheque',
        'bank_name' => 'National Bank',
        'cheque_number' => 'CHQ-123456',
        'cheque_date' => '2025-01-15',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', $paymentData);

    $response->assertCreated()
        ->assertJsonFragment(['payment_method' => 'cheque'])
        ->assertJsonFragment(['cheque_number' => 'CHQ-123456']);
});

test('payment number is auto-generated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $paymentData = [
        'shop_id' => $shop->id,
        'amount' => 10000.00,
        'payment_method' => 'cash',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', $paymentData);

    $response->assertCreated();
    $data = $response->json('data');

    expect($data['payment_number'])->toStartWith('PAY-'.now()->format('Ymd'));
});

test('payment validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['shop_id', 'amount', 'payment_method']);
});

test('payment validates payment method', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'shop_id' => $shop->id,
            'amount' => 10000,
            'payment_method' => 'invalid_method',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payment_method']);
});

test('cheque payment requires cheque number and date', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'shop_id' => $shop->id,
            'amount' => 10000,
            'payment_method' => 'cheque',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cheque_number', 'cheque_date']);
});

test('mobile money payment requires phone number', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'shop_id' => $shop->id,
            'amount' => 10000,
            'payment_method' => 'airtel_money',
            'mobile_money_details' => [],
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['mobile_money_details.phone']);
});

test('user cannot record payment for shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $paymentData = [
        'shop_id' => $shop2->id,
        'amount' => 10000,
        'payment_method' => 'cash',
    ];

    $response = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/payments', $paymentData);

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('user can view a specific payment', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $payment = Payment::factory()->create([
        'shop_id' => $shop->id,
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/payments/{$payment->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $payment->id])
        ->assertJsonFragment(['payment_number' => $payment->payment_number]);
});

test('user can view payment with included relationships', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
    $payment = Payment::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/payments/{$payment->id}?include=customer,shop");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'customer' => ['id', 'name'],
                'shop' => ['id', 'name'],
            ],
        ]);
});

test('user cannot view payment from shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $payment = Payment::factory()->create([
        'shop_id' => $shop2->id,
        'received_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/payments/{$payment->id}");

    $response->assertNotFound()
        ->assertJsonFragment(['message' => 'Payment not found or you do not have access to it.']);
});

test('payments can be filtered by customer', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer1 = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
    $customer2 = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $payment1 = Payment::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer1->id,
        'received_by' => $user->id,
    ]);
    $payment2 = Payment::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer2->id,
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/payments?customer_id={$customer1->id}");

    $response->assertOk()
        ->assertJsonFragment(['payment_number' => $payment1->payment_number])
        ->assertJsonMissing(['payment_number' => $payment2->payment_number]);
});

test('payments can be filtered by payment method', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $cashPayment = Payment::factory()->cash()->create([
        'shop_id' => $shop->id,
        'received_by' => $user->id,
    ]);
    $mobilePayment = Payment::factory()->mobileMoney()->create([
        'shop_id' => $shop->id,
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/payments?payment_method=cash');

    $response->assertOk()
        ->assertJsonFragment(['payment_number' => $cashPayment->payment_number])
        ->assertJsonMissing(['payment_number' => $mobilePayment->payment_number]);
});

test('payments can be filtered by date range', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $oldPayment = Payment::factory()->create([
        'shop_id' => $shop->id,
        'payment_date' => now()->subDays(10),
        'received_by' => $user->id,
    ]);
    $recentPayment = Payment::factory()->create([
        'shop_id' => $shop->id,
        'payment_date' => now()->subDays(2),
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/payments?from_date='.now()->subDays(5)->toDateString());

    $response->assertOk()
        ->assertJsonFragment(['payment_number' => $recentPayment->payment_number])
        ->assertJsonMissing(['payment_number' => $oldPayment->payment_number]);
});

test('payments can be searched by payment number', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $payment1 = Payment::factory()->create([
        'shop_id' => $shop->id,
        'payment_number' => 'PAY-20250109-0001',
        'received_by' => $user->id,
    ]);
    $payment2 = Payment::factory()->create([
        'shop_id' => $shop->id,
        'payment_number' => 'PAY-20250109-0002',
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/payments?search=0001');

    $response->assertOk()
        ->assertJsonFragment(['payment_number' => 'PAY-20250109-0001'])
        ->assertJsonMissing(['payment_number' => 'PAY-20250109-0002']);
});

test('payments can be filtered by specific shop', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user->id]);

    $payment1 = Payment::factory()->create([
        'shop_id' => $shop1->id,
        'received_by' => $user->id,
    ]);
    $payment2 = Payment::factory()->create([
        'shop_id' => $shop2->id,
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/payments?shop_id={$shop1->id}");

    $response->assertOk()
        ->assertJsonFragment(['payment_number' => $payment1->payment_number])
        ->assertJsonMissing(['payment_number' => $payment2->payment_number]);
});

test('user cannot filter payments by shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/payments?shop_id={$shop2->id}");

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('payments can be sorted', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $payment1 = Payment::factory()->create([
        'shop_id' => $shop->id,
        'amount' => 10000,
        'payment_date' => now()->subDays(2),
        'received_by' => $user->id,
    ]);
    $payment2 = Payment::factory()->create([
        'shop_id' => $shop->id,
        'amount' => 50000,
        'payment_date' => now()->subDay(),
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/payments?sort_by=amount&sort_order=asc');

    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['amount'])->toBe('10000.00');
});

test('payments list is paginated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Payment::factory()->count(20)->create([
        'shop_id' => $shop->id,
        'received_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/payments');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.per_page', 15);
});

test('payment can be linked to a sale', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $sale = Sale::factory()->create([
        'shop_id' => $shop->id,
        'served_by' => $user->id,
    ]);

    $paymentData = [
        'shop_id' => $shop->id,
        'sale_id' => $sale->id,
        'amount' => 10000,
        'payment_method' => 'cash',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', $paymentData);

    $response->assertCreated();

    $this->assertDatabaseHas('payments', [
        'sale_id' => $sale->id,
        'amount' => 10000,
    ]);
});

test('payment in foreign currency calculates base currency amount', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $paymentData = [
        'shop_id' => $shop->id,
        'amount' => 100.00,
        'currency' => 'USD',
        'exchange_rate' => 1650.00,
        'payment_method' => 'cash',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', $paymentData);

    $response->assertCreated()
        ->assertJsonFragment([
            'amount' => '100.00',
            'currency' => 'USD',
            'exchange_rate' => '1650.0000',
            'amount_in_base_currency' => '165000.00',
        ]);
});
