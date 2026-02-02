<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\ShopUser;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates a complete demo shop with realistic sample data for development and testing.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create a demo user (shop owner)
            $demoUser = User::create([
                'name' => 'Demo Owner',
                'email' => 'demo@tiwinebiz.com',
                'phone' => '+265999123456',
                'password_hash' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            // Create a demo shop
            $demoShop = Shop::create([
                'owner_id' => $demoUser->id,
                'name' => 'TiwineBiz Demo Electronics',
                'business_type' => 'electronics',
                'tpin' => '1234567890',
                'phone' => '+265999123456',
                'email' => 'demo@tiwinebiz.com',
                'address' => 'Victoria Avenue, Limbe',
                'city' => 'Blantyre',
                'district' => 'Blantyre',
                'country' => 'Malawi',
                'latitude' => -15.8277,
                'longitude' => 35.0367,
                'default_currency' => 'MWK',
                'fiscal_year_start_month' => 1,
                'subscription_tier' => 'professional',
                'subscription_status' => 'active',
                'subscription_started_at' => now()->subMonths(3),
                'subscription_expires_at' => now()->addMonths(9),
                'features' => ['basic_pos', 'inventory_management', 'customer_management', 'advanced_reports', 'mobile_money', 'multi_currency'],
                'limits' => ['products' => 10000, 'users' => 15, 'monthly_sales' => 50000],
                'settings' => [
                    'receipt_footer' => 'Thank you for shopping with us!',
                    'print_receipt_automatically' => true,
                    'low_stock_notification' => true,
                    'currency_display' => 'MWK',
                ],
                'is_active' => true,
            ]);

            // Get the owner role
            $ownerRole = Role::where('name', 'owner')->first();

            // Attach the user to the shop as owner
            ShopUser::create([
                'shop_id' => $demoShop->id,
                'user_id' => $demoUser->id,
                'role_id' => $ownerRole->id,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            // Get system categories
            $electronicsCategory = Category::where('slug', 'electronics')->whereNull('shop_id')->first();
            $phonesCategory = Category::where('slug', 'mobile-phones')->whereNull('shop_id')->first();
            $laptopsCategory = Category::where('slug', 'laptops')->whereNull('shop_id')->first();
            $accessoriesCategory = Category::where('slug', 'electronics-accessories')->whereNull('shop_id')->first();

            // Create suppliers
            $suppliers = Supplier::factory()
                ->count(2)
                ->for($demoShop)
                ->create([
                    'country' => 'South Africa',
                    'is_active' => true,
                ]);

            $mainSupplier = $suppliers->first();

            // Create products with realistic data
            $products = collect([
                [
                    'name' => 'Samsung Galaxy A54',
                    'name_chichewa' => 'Foni ya Samsung',
                    'description' => 'Latest Samsung smartphone with great camera',
                    'sku' => 'SAM-A54-BLK',
                    'barcode' => '8801643708914',
                    'category_id' => $phonesCategory->id,
                    'cost_price' => 850000.00,
                    'selling_price' => 1100000.00,
                    'quantity' => 5.000,
                    'unit' => 'piece',
                    'min_stock_level' => 2.000,
                    'max_stock_level' => 10.000,
                    'reorder_point' => 3.000,
                    'reorder_quantity' => 5.000,
                    'is_vat_applicable' => true,
                    'vat_rate' => 16.5,
                    'primary_supplier_id' => $mainSupplier->id,
                ],
                [
                    'name' => 'Tecno Spark 20',
                    'name_chichewa' => 'Foni ya Tecno',
                    'description' => 'Affordable smartphone with long battery life',
                    'sku' => 'TEC-SP20-GRY',
                    'barcode' => '6945139411234',
                    'category_id' => $phonesCategory->id,
                    'cost_price' => 280000.00,
                    'selling_price' => 350000.00,
                    'quantity' => 12.000,
                    'unit' => 'piece',
                    'min_stock_level' => 5.000,
                    'max_stock_level' => 20.000,
                    'reorder_point' => 8.000,
                    'reorder_quantity' => 10.000,
                    'is_vat_applicable' => true,
                    'vat_rate' => 16.5,
                    'primary_supplier_id' => $mainSupplier->id,
                ],
                [
                    'name' => 'HP Pavilion 15',
                    'name_chichewa' => 'Laptop ya HP',
                    'description' => 'Intel Core i5, 8GB RAM, 512GB SSD',
                    'sku' => 'HP-PAV15-I5',
                    'barcode' => '195161149430',
                    'category_id' => $laptopsCategory->id,
                    'cost_price' => 1800000.00,
                    'selling_price' => 2200000.00,
                    'quantity' => 3.000,
                    'unit' => 'piece',
                    'min_stock_level' => 1.000,
                    'max_stock_level' => 5.000,
                    'reorder_point' => 2.000,
                    'reorder_quantity' => 3.000,
                    'is_vat_applicable' => true,
                    'vat_rate' => 16.5,
                    'primary_supplier_id' => $mainSupplier->id,
                ],
                [
                    'name' => 'Phone Charger (Type-C)',
                    'name_chichewa' => 'Chaja ya Foni',
                    'description' => 'Fast charging USB Type-C charger',
                    'sku' => 'CHG-USBC-20W',
                    'barcode' => '6970404371234',
                    'category_id' => $accessoriesCategory->id,
                    'cost_price' => 8000.00,
                    'selling_price' => 15000.00,
                    'quantity' => 45.000,
                    'unit' => 'piece',
                    'min_stock_level' => 10.000,
                    'max_stock_level' => 100.000,
                    'reorder_point' => 20.000,
                    'reorder_quantity' => 50.000,
                    'is_vat_applicable' => true,
                    'vat_rate' => 16.5,
                    'primary_supplier_id' => $mainSupplier->id,
                ],
                [
                    'name' => 'Phone Case (Universal)',
                    'name_chichewa' => 'Chikopa cha Foni',
                    'description' => 'Protective silicone phone case',
                    'sku' => 'CASE-SILC-UNI',
                    'barcode' => '6941812704567',
                    'category_id' => $accessoriesCategory->id,
                    'cost_price' => 3000.00,
                    'selling_price' => 6000.00,
                    'quantity' => 78.000,
                    'unit' => 'piece',
                    'min_stock_level' => 20.000,
                    'max_stock_level' => 150.000,
                    'reorder_point' => 30.000,
                    'reorder_quantity' => 100.000,
                    'is_vat_applicable' => true,
                    'vat_rate' => 16.5,
                    'primary_supplier_id' => $mainSupplier->id,
                ],
            ]);

            $createdProducts = [];
            foreach ($products as $productData) {
                $createdProducts[] = Product::create(array_merge($productData, [
                    'shop_id' => $demoShop->id,
                    'base_currency' => 'MWK',
                    'is_active' => true,
                    'created_by' => $demoUser->id,
                ]));
            }

            // Create customers
            $customers = Customer::factory()
                ->count(3)
                ->for($demoShop)
                ->create([
                    'created_by' => $demoUser->id,
                ]);

            // Create one customer with credit
            $customerWithCredit = Customer::factory()
                ->for($demoShop)
                ->withCredit()
                ->create([
                    'name' => 'John Banda',
                    'phone' => '+265888555777',
                    'city' => 'Blantyre',
                    'credit_limit' => 500000.00,
                    'current_balance' => 150000.00,
                    'trust_level' => 'trusted',
                    'created_by' => $demoUser->id,
                ]);

            $allCustomers = $customers->push($customerWithCredit);

            // Create sales
            $saleStatuses = ['paid', 'paid', 'paid', 'partial', 'pending'];

            foreach ($saleStatuses as $index => $status) {
                $customer = $allCustomers->random();
                $saleDate = now()->subDays(rand(1, 30));

                // Select 1-3 random products for this sale
                $saleProducts = collect($createdProducts)->random(rand(1, 3));

                $subtotal = 0;
                $saleItems = [];

                foreach ($saleProducts as $product) {
                    $quantity = rand(1, 3);
                    $unitPrice = $product->selling_price;
                    $lineTotal = $unitPrice * $quantity;
                    $subtotal += $lineTotal;

                    $saleItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];
                }

                $discountPercentage = 0;
                $discountAmount = 0;
                $afterDiscount = $subtotal;
                $taxAmount = $afterDiscount * 0.165;
                $totalAmount = $afterDiscount + $taxAmount;

                $amountPaid = match ($status) {
                    'paid' => $totalAmount,
                    'partial' => $totalAmount * 0.5,
                    'pending' => 0,
                };

                $balance = $totalAmount - $amountPaid;

                $sale = Sale::create([
                    'shop_id' => $demoShop->id,
                    'branch_id' => null,
                    'sale_number' => 'SALE-'.now()->format('Ymd').'-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    'customer_id' => $customer->id,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'discount_percentage' => $discountPercentage,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'amount_paid' => $amountPaid,
                    'balance' => $balance,
                    'change_given' => 0,
                    'payment_status' => $status,
                    'payment_methods' => [
                        ['method' => 'cash', 'amount' => $amountPaid],
                    ],
                    'currency' => 'MWK',
                    'exchange_rate' => 1.0000,
                    'amount_in_base_currency' => $totalAmount,
                    'is_fiscalized' => false,
                    'sale_type' => 'pos',
                    'sale_date' => $saleDate,
                    'completed_at' => $saleDate,
                    'served_by' => $demoUser->id,
                ]);

                // Create sale items
                foreach ($saleItems as $itemData) {
                    $itemSubtotal = $itemData['line_total'];
                    $itemTaxAmount = $itemSubtotal * 0.165;
                    $itemTotal = $itemSubtotal + $itemTaxAmount;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $itemData['product']->id,
                        'product_name' => $itemData['product']->name,
                        'product_sku' => $itemData['product']->sku,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'unit_cost' => $itemData['product']->cost_price,
                        'discount_amount' => 0,
                        'discount_percentage' => 0,
                        'is_taxable' => true,
                        'tax_rate' => 16.5,
                        'tax_amount' => $itemTaxAmount,
                        'subtotal' => $itemSubtotal,
                        'total' => $itemTotal,
                    ]);
                }
            }

            // Create a purchase order
            $purchaseOrder = PurchaseOrder::create([
                'shop_id' => $demoShop->id,
                'supplier_id' => $mainSupplier->id,
                'po_number' => 'PO-'.now()->format('Ymd').'-0001',
                'order_date' => now()->subDays(7),
                'expected_delivery_date' => now()->addDays(7),
                'subtotal' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'currency' => 'MWK',
                'status' => 'draft',
                'notes' => 'Initial stock order for new products',
                'created_by' => $demoUser->id,
            ]);

            // Add items to purchase order
            $poItems = [
                [
                    'product' => $createdProducts[0],
                    'quantity' => 5,
                ],
                [
                    'product' => $createdProducts[1],
                    'quantity' => 10,
                ],
            ];

            $poSubtotal = 0;

            foreach ($poItems as $itemData) {
                $lineTotal = $itemData['product']->cost_price * $itemData['quantity'];
                $poSubtotal += $lineTotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $itemData['product']->id,
                    'product_name' => $itemData['product']->name,
                    'product_code' => $itemData['product']->sku,
                    'quantity_ordered' => $itemData['quantity'],
                    'quantity_received' => 0,
                    'unit_price' => $itemData['product']->cost_price,
                    'subtotal' => $lineTotal,
                ]);
            }

            $poTaxAmount = $poSubtotal * 0.165;
            $poTotalAmount = $poSubtotal + $poTaxAmount;

            $purchaseOrder->update([
                'subtotal' => $poSubtotal,
                'tax_amount' => $poTaxAmount,
                'total_amount' => $poTotalAmount,
            ]);
        });
    }
}
