<?php

use App\Models\Credit;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access credits', function () {
    $response = $this->getJson('/api/v1/credits');

    $response->assertUnauthorized();
});

test('authenticated user can list credits from their shops', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
    $credit = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/credits');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'credit_number', 'original_amount', 'balance'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonFragment(['credit_number' => $credit->credit_number]);
});

test('user can only see credits from shops they have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $shop1 = Shop::factory()->create(['owner_id' => $user1->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $credit1 = Credit::factory()->create([
        'shop_id' => $shop1->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop1->id])->id,
        'created_by' => $user1->id,
    ]);
    $credit2 = Credit::factory()->create([
        'shop_id' => $shop2->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop2->id])->id,
        'created_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson('/api/v1/credits');

    $response->assertOk()
        ->assertJsonFragment(['credit_number' => $credit1->credit_number])
        ->assertJsonMissing(['credit_number' => $credit2->credit_number]);
});

test('user can create a credit', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $creditData = [
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'original_amount' => 50000.00,
        'issue_date' => '2025-01-10',
        'due_date' => '2025-02-10',
        'payment_term' => 'mwezi_umodzi',
        'notes' => 'Customer requested 30 days credit',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credits', $creditData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'credit_number', 'balance'],
        ])
        ->assertJsonFragment(['original_amount' => '50000.00']);

    $this->assertDatabaseHas('credits', [
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'original_amount' => 50000.00,
        'balance' => 50000.00,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);
});

test('credit number is auto-generated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);

    $creditData = [
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'original_amount' => 10000.00,
        'issue_date' => '2025-01-10',
        'due_date' => '2025-01-20',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credits', $creditData);

    $response->assertCreated();
    $data = $response->json('data');

    expect($data['credit_number'])->toStartWith('CREDIT-'.now()->format('Ymd'));
});

test('credit validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credits', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['shop_id', 'customer_id', 'original_amount', 'issue_date', 'due_date']);
});

test('due date must be after or equal to issue date', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create(['shop_id' => $shop->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credits', [
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'original_amount' => 10000,
            'issue_date' => '2025-01-20',
            'due_date' => '2025-01-10',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['due_date']);
});

test('user cannot create credit for shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $customer = Customer::factory()->create(['shop_id' => $shop2->id]);

    $creditData = [
        'shop_id' => $shop2->id,
        'customer_id' => $customer->id,
        'original_amount' => 10000,
        'issue_date' => '2025-01-10',
        'due_date' => '2025-01-20',
    ];

    $response = $this->actingAs($user1, 'sanctum')
        ->postJson('/api/v1/credits', $creditData);

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('user can view a specific credit', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $credit = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/credits/{$credit->id}");

    $response->assertOk()
        ->assertJsonFragment(['id' => $credit->id])
        ->assertJsonFragment(['credit_number' => $credit->credit_number]);
});

test('user can view credit with included relationships', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'created_by' => $user->id,
    ]);
    $credit = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/credits/{$credit->id}?include=customer,shop");

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'customer' => ['id', 'name'],
                'shop' => ['id', 'name'],
            ],
        ]);
});

test('user cannot view credit from shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);
    $credit = Credit::factory()->create([
        'shop_id' => $shop2->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop2->id])->id,
        'created_by' => $user2->id,
    ]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/credits/{$credit->id}");

    $response->assertNotFound()
        ->assertJsonFragment(['message' => 'Credit not found or you do not have access to it.']);
});

test('user can update a credit', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $credit = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'original_amount' => 50000,
        'amount_paid' => 0,
        'balance' => 50000,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    $updateData = [
        'amount_paid' => 25000.00,
        'notes' => 'Customer made partial payment',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/credits/{$credit->id}", $updateData);

    $response->assertOk()
        ->assertJsonFragment(['amount_paid' => '25000.00'])
        ->assertJsonFragment(['balance' => '25000.00'])
        ->assertJsonFragment(['status' => 'partial']);
});

test('credit status automatically updates to paid when fully paid', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $credit = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'original_amount' => 50000,
        'amount_paid' => 0,
        'balance' => 50000,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/credits/{$credit->id}", [
            'amount_paid' => 50000.00,
        ]);

    $response->assertOk()
        ->assertJsonFragment(['status' => 'paid'])
        ->assertJsonFragment(['balance' => '0.00']);

    $credit->refresh();
    expect($credit->paid_at)->not->toBeNull();
});

test('user can write off a credit', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $credit = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/credits/{$credit->id}", [
            'reason' => 'Customer deceased',
        ]);

    $response->assertOk()
        ->assertJsonFragment(['message' => 'Credit written off successfully.']);

    $this->assertDatabaseHas('credits', [
        'id' => $credit->id,
        'status' => 'written_off',
        'written_off_by' => $user->id,
        'write_off_reason' => 'Customer deceased',
    ]);
});

test('credits can be filtered by customer', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer1 = Customer::factory()->create(['shop_id' => $shop->id]);
    $customer2 = Customer::factory()->create(['shop_id' => $shop->id]);

    $credit1 = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer1->id,
        'created_by' => $user->id,
    ]);
    $credit2 = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => $customer2->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/credits?customer_id={$customer1->id}");

    $response->assertOk()
        ->assertJsonFragment(['credit_number' => $credit1->credit_number])
        ->assertJsonMissing(['credit_number' => $credit2->credit_number]);
});

test('credits can be filtered by status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $pendingCredit = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);
    $paidCredit = Credit::factory()->paid()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/credits?status=pending');

    $response->assertOk()
        ->assertJsonFragment(['credit_number' => $pendingCredit->credit_number])
        ->assertJsonMissing(['credit_number' => $paidCredit->credit_number]);
});

test('credits can be filtered by overdue status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $overdueCredit = Credit::factory()->overdue()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'created_by' => $user->id,
    ]);
    $currentCredit = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'due_date' => now()->addDays(30),
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/credits?is_overdue=true');

    $response->assertOk()
        ->assertJsonFragment(['credit_number' => $overdueCredit->credit_number])
        ->assertJsonMissing(['credit_number' => $currentCredit->credit_number]);
});

test('credits can be filtered by due date range', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $credit1 = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'due_date' => '2025-01-15',
        'created_by' => $user->id,
    ]);
    $credit2 = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'due_date' => '2025-02-15',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/credits?due_from=2025-02-01&due_to=2025-02-28');

    $response->assertOk()
        ->assertJsonFragment(['credit_number' => $credit2->credit_number])
        ->assertJsonMissing(['credit_number' => $credit1->credit_number]);
});

test('credits can be searched by credit number', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $credit1 = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'credit_number' => 'CREDIT-20250109-0001',
        'created_by' => $user->id,
    ]);
    $credit2 = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'credit_number' => 'CREDIT-20250109-0002',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/credits?search=0001');

    $response->assertOk()
        ->assertJsonFragment(['credit_number' => 'CREDIT-20250109-0001'])
        ->assertJsonMissing(['credit_number' => 'CREDIT-20250109-0002']);
});

test('credits can be filtered by specific shop', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['owner_id' => $user->id]);
    $shop2 = Shop::factory()->create(['owner_id' => $user->id]);

    $credit1 = Credit::factory()->create([
        'shop_id' => $shop1->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop1->id])->id,
        'created_by' => $user->id,
    ]);
    $credit2 = Credit::factory()->create([
        'shop_id' => $shop2->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop2->id])->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/credits?shop_id={$shop1->id}");

    $response->assertOk()
        ->assertJsonFragment(['credit_number' => $credit1->credit_number])
        ->assertJsonMissing(['credit_number' => $credit2->credit_number]);
});

test('user cannot filter credits by shop they do not have access to', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $shop2 = Shop::factory()->create(['owner_id' => $user2->id]);

    $response = $this->actingAs($user1, 'sanctum')
        ->getJson("/api/v1/credits?shop_id={$shop2->id}");

    $response->assertForbidden()
        ->assertJsonFragment(['message' => 'You do not have access to this shop.']);
});

test('credits can be sorted', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    $credit1 = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'original_amount' => 10000,
        'created_by' => $user->id,
    ]);
    $credit2 = Credit::factory()->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'original_amount' => 50000,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/credits?sort_by=original_amount&sort_order=asc');

    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['original_amount'])->toBe('10000.00');
});

test('credits list is paginated', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);

    Credit::factory()->count(20)->create([
        'shop_id' => $shop->id,
        'customer_id' => Customer::factory()->create(['shop_id' => $shop->id])->id,
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/credits');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.per_page', 15);
});

test('credit with initial partial payment has correct status', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['owner_id' => $user->id]);
    $customer = Customer::factory()->create(['shop_id' => $shop->id]);

    $creditData = [
        'shop_id' => $shop->id,
        'customer_id' => $customer->id,
        'original_amount' => 100000.00,
        'amount_paid' => 40000.00,
        'issue_date' => '2025-01-10',
        'due_date' => '2025-02-10',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/credits', $creditData);

    $response->assertCreated()
        ->assertJsonFragment(['status' => 'partial'])
        ->assertJsonFragment(['balance' => '60000.00']);
});
