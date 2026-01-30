<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TVProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates TV products for the demo shop.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Get or create shop
            $shop = Shop::first();
            if (! $shop) {
                $user = User::create([
                    'name' => 'Demo Owner',
                    'email' => 'demo@tiwinebiz.com',
                    'phone' => '+265999123456',
                    'password_hash' => Hash::make('password'),
                ]);

                $shop = Shop::create([
                    'owner_id' => $user->id,
                    'name' => 'TiwineBiz Demo Electronics',
                    'business_type' => 'electronics',
                    'tpin' => '1234567890',
                    'phone' => '+265999123456',
                    'email' => 'demo@tiwinebiz.com',
                    'address' => 'Victoria Avenue, Limbe',
                    'city' => 'Blantyre',
                    'default_currency' => 'MWK',
                    'is_active' => true,
                ]);
            }

            // Get electronics category
            $electronicsCategory = Category::where('slug', 'electronics')
                ->whereNull('shop_id')
                ->first();

            if (! $electronicsCategory) {
                $this->command->error('Electronics category not found! Run CategorySeeder first.');

                return;
            }

            // Create TV products
            $tvs = [
                [
                    'name' => 'Samsung 43" Smart TV 4K',
                    'description' => '43-inch 4K UHD Smart TV with HDR',
                    'sku' => 'SAM-TV43-4K',
                    'barcode' => '8801643771234',
                    'cost_price' => 450000.00,
                    'selling_price' => 650000.00,
                    'quantity' => 8,
                ],
                [
                    'name' => 'LG 50" NanoCell TV',
                    'description' => '50-inch NanoCell TV with AI Processor',
                    'sku' => 'LG-TV50-NC',
                    'barcode' => '8806084123456',
                    'cost_price' => 680000.00,
                    'selling_price' => 920000.00,
                    'quantity' => 5,
                ],
                [
                    'name' => 'Hisense 55" ULED TV',
                    'description' => '55-inch 4K ULED Smart TV',
                    'sku' => 'HIS-TV55-UL',
                    'barcode' => '6940567890123',
                    'cost_price' => 720000.00,
                    'selling_price' => 950000.00,
                    'quantity' => 6,
                ],
                [
                    'name' => 'Samsung 65" QLED TV',
                    'description' => '65-inch QLED 4K Smart TV with Quantum Dot',
                    'sku' => 'SAM-TV65-QD',
                    'barcode' => '8801643789012',
                    'cost_price' => 1450000.00,
                    'selling_price' => 1850000.00,
                    'quantity' => 3,
                ],
                [
                    'name' => 'TCL 55" Roku TV',
                    'description' => '55-inch 4K UHD Roku Smart TV',
                    'sku' => 'TCL-TV55-RK',
                    'barcode' => '6970405678901',
                    'cost_price' => 580000.00,
                    'selling_price' => 780000.00,
                    'quantity' => 10,
                ],
                [
                    'name' => 'Sony 48" OLED TV',
                    'description' => '48-inch OLED 4K UHD Smart TV',
                    'sku' => 'SNY-TV48-OL',
                    'barcode' => '8806098234567',
                    'cost_price' => 1850000.00,
                    'selling_price' => 2350000.00,
                    'quantity' => 2,
                ],
                [
                    'name' => 'LG 43" HD TV',
                    'description' => '43-inch HD Smart TV - Budget Friendly',
                    'sku' => 'LG-TV43-HD',
                    'barcode' => '8806084567890',
                    'cost_price' => 320000.00,
                    'selling_price' => 450000.00,
                    'quantity' => 15,
                ],
                [
                    'name' => 'Samsung 55" Crystal UHD TV',
                    'description' => '55-inch Crystal UHD 4K Smart TV',
                    'sku' => 'SAM-TV55-CU',
                    'barcode' => '8801643890123',
                    'cost_price' => 850000.00,
                    'selling_price' => 1100000.00,
                    'quantity' => 7,
                ],
                [
                    'name' => 'Hisense 65" ULED TV',
                    'description' => '65-inch ULED 4K with Dolby Vision',
                    'sku' => 'HIS-TV65-UL',
                    'barcode' => '6940567234567',
                    'cost_price' => 1250000.00,
                    'selling_price' => 1650000.00,
                    'quantity' => 4,
                ],
                [
                    'name' => 'TCL 65" QLED TV',
                    'description' => '65-inch QLED 4K UHD Google TV',
                    'sku' => 'TCL-TV65-QL',
                    'barcode' => '6970406789012',
                    'cost_price' => 1100000.00,
                    'selling_price' => 1450000.00,
                    'quantity' => 5,
                ],
                [
                    'name' => 'Samsung 75" QLED TV',
                    'description' => '75-inch QLED 4K Smart TV - Premium',
                    'sku' => 'SAM-TV75-QD',
                    'barcode' => '8801643901234',
                    'cost_price' => 2200000.00,
                    'selling_price' => 2850000.00,
                    'quantity' => 2,
                ],
                [
                    'name' => 'LG 55" OLED TV',
                    'description' => '55-inch OLED evo 4K Smart TV',
                    'sku' => 'LG-TV55-OL',
                    'barcode' => '8806084345678',
                    'cost_price' => 1950000.00,
                    'selling_price' => 2500000.00,
                    'quantity' => 3,
                ],
                [
                    'name' => 'Hisense 43" HD TV',
                    'description' => '43-inch HD TV - Value Series',
                    'sku' => 'HIS-TV43-HD',
                    'barcode' => '6940567345678',
                    'cost_price' => 280000.00,
                    'selling_price' => 380000.00,
                    'quantity' => 12,
                ],
                [
                    'name' => 'TCL 43" FHD TV',
                    'description' => '43-inch Full HD Smart TV',
                    'sku' => 'TCL-TV43-FH',
                    'barcode' => '6970405345678',
                    'cost_price' => 350000.00,
                    'selling_price' => 480000.00,
                    'quantity' => 9,
                ],
                [
                    'name' => 'Samsung 50" Crystal UHD TV',
                    'description' => '50-inch Crystal UHD 4K Smart TV',
                    'sku' => 'SAM-TV50-CU',
                    'barcode' => '8801643456789',
                    'cost_price' => 620000.00,
                    'selling_price' => 820000.00,
                    'quantity' => 6,
                ],
                [
                    'name' => 'Skyworth 43" FHD TV',
                    'description' => '43-inch Full HD Android TV',
                    'sku' => 'SKY-TV43-FH',
                    'barcode' => '6940567456789',
                    'cost_price' => 300000.00,
                    'selling_price' => 420000.00,
                    'quantity' => 11,
                ],
                [
                    'name' => 'LG 65" NanoCell TV',
                    'description' => '65-inch NanoCell 4K Smart TV',
                    'sku' => 'LG-TV65-NC',
                    'barcode' => '8806084456789',
                    'cost_price' => 1350000.00,
                    'selling_price' => 1750000.00,
                    'quantity' => 4,
                ],
                [
                    'name' => 'Samsung 85" QLED TV',
                    'description' => '85-inch QLED 8K Smart TV - Ultra Premium',
                    'sku' => 'SAM-TV85-8K',
                    'barcode' => '8801643012345',
                    'cost_price' => 3500000.00,
                    'selling_price' => 4500000.00,
                    'quantity' => 1,
                ],
                [
                    'name' => 'TCL 75" QLED TV',
                    'description' => '75-inch QLED 4K UHD Google TV',
                    'sku' => 'TCL-TV75-QL',
                    'barcode' => '6970407012345',
                    'cost_price' => 1650000.00,
                    'selling_price' => 2150000.00,
                    'quantity' => 3,
                ],
                [
                    'name' => 'Hisense 75" ULED TV',
                    'description' => '75-inch ULED 4K with Dolby Atmos',
                    'sku' => 'HIS-TV75-UL',
                    'barcode' => '6940567567890',
                    'cost_price' => 1800000.00,
                    'selling_price' => 2300000.00,
                    'quantity' => 2,
                ],
            ];

            $created = 0;
            foreach ($tvs as $tvData) {
                $minPrice = $tvData['cost_price'] * 1.1;

                Product::create(array_merge($tvData, [
                    'id' => Str::uuid(),
                    'shop_id' => $shop->id,
                    'category_id' => $electronicsCategory->id,
                    'min_price' => $minPrice,
                    'base_currency' => 'MWK',
                    'unit' => 'piece',
                    'min_stock_level' => 2,
                    'max_stock_level' => 15,
                    'reorder_point' => 5,
                    'reorder_quantity' => 5,
                    'is_vat_applicable' => true,
                    'vat_rate' => 16.5,
                    'tax_category' => 'standard',
                    'is_active' => true,
                    'total_sold' => rand(0, 50),
                    'total_revenue' => rand(0, 5000000),
                ]));
                $created++;
            }

            $this->command->info("Successfully created {$created} TV products for shop: {$shop->name}");
            $this->command->info("Shop ID: {$shop->id}");
        });
    }
}
