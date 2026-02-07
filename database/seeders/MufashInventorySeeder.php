<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MufashInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates Mufash Electronics inventory with realistic products and quantities.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Get Mufash Electronics shop and admin user
            $shop = Shop::where('name', 'Mufasah Electronics')->first();
            $admin = User::where('email', 'admin@mufashelectronics.com')->first();

            if (! $shop || ! $admin) {
                $this->command->error('Mufash Electronics shop or admin user not found. Please run AdminSeeder first.');

                return;
            }

            $this->command->info('Seeding inventory for Mufash Electronics...');

            // Get or create electronics category
            $electronics = Category::where('slug', 'electronics')->whereNull('shop_id')->first();

            // Create subcategories under Electronics
            $televisionsCategory = $this->createCategory($shop, 'Televisions', 'Televizioni', $electronics);
            $audioCategory = $this->createCategory($shop, 'Audio & Sound Systems', 'Zimamaso', $electronics);
            $decodersCategory = $this->createCategory($shop, 'TV Decoders', 'Makitole', $electronics);

            // Get existing categories
            $accessoriesCategory = Category::where('slug', 'electronics-accessories')->whereNull('shop_id')->first();
            $householdCategory = Category::where('slug', 'household-items')->whereNull('shop_id')->first();

            // Seed Televisions
            $this->seedTelevisions($shop, $televisionsCategory, $admin);

            // Seed Audio & Sound Systems
            $this->seedAudioSystems($shop, $audioCategory, $admin);

            // Seed Accessories
            $this->seedAccessories($shop, $accessoriesCategory, $admin);

            // Seed TV Decoders
            $this->seedDecoders($shop, $decodersCategory, $admin);

            // Seed Household Items
            $this->seedHouseholdItems($shop, $householdCategory, $admin);

            $this->command->info('Mufash Electronics inventory seeded successfully!');
        });
    }

    private function createCategory(Shop $shop, string $name, string $nameChichewa, ?Category $parent = null): Category
    {
        $slug = Str::slug($name);
        $depth = $parent ? $parent->depth + 1 : 0;

        return Category::firstOrCreate(
            [
                'shop_id' => null,
                'slug' => $slug,
            ],
            [
                'parent_id' => $parent?->id,
                'name' => $name,
                'name_chichewa' => $nameChichewa,
                'description' => "{$name} for Mufash Electronics",
                'depth' => $depth,
                'display_order' => 1,
                'is_active' => true,
            ]
        );
    }

    private function seedTelevisions(Shop $shop, Category $category, User $admin): void
    {
        $this->command->info('Seeding Televisions...');

        $tvProducts = [
            // Working TVs
            [
                'name' => 'TV 75" Smart 4K',
                'sku' => 'TV-75-SMART-SAM',
                'quantity' => 1,
                'cost_price' => 650000,
                'selling_price' => 850000,
                'brand' => 'Samsung',
            ],
            [
                'name' => 'TV 60" Smart 4K',
                'sku' => 'TV-60-SMART-LG',
                'quantity' => 2,
                'cost_price' => 520000,
                'selling_price' => 680000,
                'brand' => 'LG',
            ],
            [
                'name' => 'TV 50" Smart LED',
                'sku' => 'TV-50-SMART-HIS',
                'quantity' => 23,
                'cost_price' => 280000,
                'selling_price' => 380000,
                'brand' => 'Hisense',
            ],
            [
                'name' => 'TV 50" Normal LED',
                'sku' => 'TV-50-NORMAL-SKY',
                'quantity' => 1,
                'cost_price' => 220000,
                'selling_price' => 300000,
                'brand' => 'Skyworth',
            ],
            [
                'name' => 'TV 45" Smart LED',
                'sku' => 'TV-45-SMART-SNY',
                'quantity' => 14,
                'cost_price' => 200000,
                'selling_price' => 270000,
                'brand' => 'Sony',
            ],
            [
                'name' => 'TV 45" Normal LED',
                'sku' => 'TV-45-NORMAL-TCL',
                'quantity' => 1,
                'cost_price' => 160000,
                'selling_price' => 220000,
                'brand' => 'TCL',
            ],
            [
                'name' => 'TV 43" Normal LED',
                'sku' => 'TV-43-NORMAL-PHI',
                'quantity' => 3,
                'cost_price' => 150000,
                'selling_price' => 200000,
                'brand' => 'Philips',
            ],
            [
                'name' => 'TV 32" Smart LED',
                'sku' => 'TV-32-SMART-SAM',
                'quantity' => 7,
                'cost_price' => 120000,
                'selling_price' => 165000,
                'brand' => 'Samsung',
            ],
            [
                'name' => 'TV 32" Normal LED',
                'sku' => 'TV-32-NORMAL-LG',
                'quantity' => 12,
                'cost_price' => 95000,
                'selling_price' => 135000,
                'brand' => 'LG',
            ],
            [
                'name' => 'TV 29" Normal LED',
                'sku' => 'TV-29-NORMAL-HIS',
                'quantity' => 1,
                'cost_price' => 85000,
                'selling_price' => 120000,
                'brand' => 'Hisense',
            ],
            // Fault TVs
            [
                'name' => 'TV 100" Smart 4K (Fault)',
                'sku' => 'TV-100-SMART-FLT',
                'quantity' => 1,
                'cost_price' => 1200000,
                'selling_price' => 1500000,
                'brand' => 'Samsung',
                'is_faulty' => true,
            ],
            [
                'name' => 'TV 60" Smart 4K (Fault)',
                'sku' => 'TV-60-SMART-FLT',
                'quantity' => 1,
                'cost_price' => 520000,
                'selling_price' => 680000,
                'brand' => 'LG',
                'is_faulty' => true,
            ],
            [
                'name' => 'TV 50" Smart LED (Fault)',
                'sku' => 'TV-50-SMART-FLT',
                'quantity' => 2,
                'cost_price' => 280000,
                'selling_price' => 380000,
                'brand' => 'Hisense',
                'is_faulty' => true,
            ],
            [
                'name' => 'TV 45" Smart LED (Fault)',
                'sku' => 'TV-45-SMART-FLT',
                'quantity' => 2,
                'cost_price' => 200000,
                'selling_price' => 270000,
                'brand' => 'Sony',
                'is_faulty' => true,
            ],
            [
                'name' => 'TV 43" Normal LED (Fault)',
                'sku' => 'TV-43-NORMAL-FLT',
                'quantity' => 1,
                'cost_price' => 150000,
                'selling_price' => 200000,
                'brand' => 'Philips',
                'is_faulty' => true,
            ],
            [
                'name' => 'TV 32" Smart LED (Fault)',
                'sku' => 'TV-32-SMART-FLT',
                'quantity' => 2,
                'cost_price' => 120000,
                'selling_price' => 165000,
                'brand' => 'Samsung',
                'is_faulty' => true,
            ],
        ];

        $totalTvs = 0;
        foreach ($tvProducts as $productData) {
            $totalTvs += $this->createProduct($shop, $category, $admin, $productData);
        }

        $this->command->info("✓ Seeded {$totalTvs} Televisions (including fault units)");
    }

    private function seedAudioSystems(Shop $shop, Category $category, User $admin): void
    {
        $this->command->info('Seeding Audio & Sound Systems...');

        $audioProducts = [
            [
                'name' => 'JVC Sound Bar',
                'sku' => 'SB-JVC-STD',
                'quantity' => 4,
                'cost_price' => 45000,
                'selling_price' => 65000,
            ],
            [
                'name' => 'JVC Sound Bar Big',
                'sku' => 'SB-JVC-BIG',
                'quantity' => 6,
                'cost_price' => 65000,
                'selling_price' => 90000,
            ],
            [
                'name' => 'JVE Sound Bar',
                'sku' => 'SB-JVE-STD',
                'quantity' => 1,
                'cost_price' => 40000,
                'selling_price' => 55000,
            ],
            [
                'name' => 'Qsonic Sound Bar',
                'sku' => 'SB-QSONIC',
                'quantity' => 1,
                'cost_price' => 50000,
                'selling_price' => 70000,
            ],
            [
                'name' => 'Vztec Sound Bar',
                'sku' => 'SB-VZTEC',
                'quantity' => 7,
                'cost_price' => 35000,
                'selling_price' => 50000,
            ],
            [
                'name' => 'Subwoofer L3001',
                'sku' => 'SW-L3001',
                'quantity' => 4,
                'cost_price' => 55000,
                'selling_price' => 75000,
            ],
            [
                'name' => 'Sky City Soundbar',
                'sku' => 'SB-SKYCITY',
                'quantity' => 3,
                'cost_price' => 42000,
                'selling_price' => 60000,
            ],
            [
                'name' => 'Bluetooth Speaker Gegi',
                'sku' => 'SP-BT-GEGI',
                'quantity' => 3,
                'cost_price' => 25000,
                'selling_price' => 38000,
            ],
            [
                'name' => 'Bluetooth Speaker Epe',
                'sku' => 'SP-BT-EPE',
                'quantity' => 3,
                'cost_price' => 28000,
                'selling_price' => 42000,
            ],
            [
                'name' => 'Bluetooth Speaker with Stand',
                'sku' => 'SP-BT-STAND',
                'quantity' => 1,
                'cost_price' => 45000,
                'selling_price' => 65000,
            ],
            [
                'name' => 'Eagle Soundbar',
                'sku' => 'SB-EAGLE',
                'quantity' => 4,
                'cost_price' => 38000,
                'selling_price' => 55000,
            ],
            [
                'name' => 'Lunar Bluetooth Speaker',
                'sku' => 'SP-BT-LUNAR',
                'quantity' => 1,
                'cost_price' => 30000,
                'selling_price' => 45000,
            ],
        ];

        $totalAudio = 0;
        foreach ($audioProducts as $productData) {
            $totalAudio += $this->createProduct($shop, $category, $admin, $productData);
        }

        $this->command->info("✓ Seeded {$totalAudio} Audio & Sound items");
    }

    private function seedAccessories(Shop $shop, Category $category, User $admin): void
    {
        $this->command->info('Seeding Accessories...');

        $accessoryProducts = [
            [
                'name' => 'HDMI Cable 1.5m',
                'sku' => 'ACC-HDMI-1.5',
                'quantity' => 12,
                'cost_price' => 4000,
                'selling_price' => 8000,
            ],
            [
                'name' => 'TV Guard',
                'sku' => 'ACC-TVGUARD',
                'quantity' => 14,
                'cost_price' => 8000,
                'selling_price' => 15000,
            ],
            [
                'name' => 'Flash Drive 32GB',
                'sku' => 'ACC-FLASH-32',
                'quantity' => 2,
                'cost_price' => 12000,
                'selling_price' => 18000,
            ],
            [
                'name' => 'Extension Cord 5m',
                'sku' => 'ACC-EXT-5M',
                'quantity' => 1,
                'cost_price' => 15000,
                'selling_price' => 25000,
            ],
            [
                'name' => 'Wall Mount Big (50-75 inch)',
                'sku' => 'ACC-WMNT-BIG',
                'quantity' => 22,
                'cost_price' => 18000,
                'selling_price' => 30000,
            ],
            [
                'name' => 'Wall Mount Small (32-43 inch)',
                'sku' => 'ACC-WMNT-SML',
                'quantity' => 6,
                'cost_price' => 12000,
                'selling_price' => 20000,
            ],
            [
                'name' => 'Heavy Duty Wall Mount',
                'sku' => 'ACC-WMNT-HVY',
                'quantity' => 0,
                'cost_price' => 25000,
                'selling_price' => 40000,
            ],
        ];

        $totalAccessories = 0;
        foreach ($accessoryProducts as $productData) {
            $totalAccessories += $this->createProduct($shop, $category, $admin, $productData);
        }

        $this->command->info("✓ Seeded {$totalAccessories} Accessories");
    }

    private function seedDecoders(Shop $shop, Category $category, User $admin): void
    {
        $this->command->info('Seeding TV Decoders...');

        $decoderProducts = [
            [
                'name' => 'TV Box Smart Android',
                'sku' => 'DEC-TVBOX',
                'quantity' => 2,
                'cost_price' => 45000,
                'selling_price' => 65000,
            ],
            [
                'name' => 'GO TV Decoder',
                'sku' => 'DEC-GOTV',
                'quantity' => 0,
                'cost_price' => 35000,
                'selling_price' => 50000,
            ],
            [
                'name' => 'DSTV Decoder',
                'sku' => 'DEC-DSTV',
                'quantity' => 6,
                'cost_price' => 55000,
                'selling_price' => 75000,
            ],
        ];

        $totalDecoders = 0;
        foreach ($decoderProducts as $productData) {
            $totalDecoders += $this->createProduct($shop, $category, $admin, $productData);
        }

        $this->command->info("✓ Seeded {$totalDecoders} TV Decoders");
    }

    private function seedHouseholdItems(Shop $shop, Category $category, User $admin): void
    {
        $this->command->info('Seeding Household Items...');

        $householdProducts = [
            [
                'name' => 'Electric Standing Fan',
                'sku' => 'HH-FAN-STAND',
                'quantity' => 6,
                'cost_price' => 35000,
                'selling_price' => 50000,
            ],
            [
                'name' => 'TV Stand Gold',
                'sku' => 'HH-TVSTAND-GD',
                'quantity' => 3,
                'cost_price' => 45000,
                'selling_price' => 65000,
            ],
            [
                'name' => 'Office Chair',
                'sku' => 'HH-CHAIR-OFC',
                'quantity' => 6,
                'cost_price' => 55000,
                'selling_price' => 80000,
            ],
            [
                'name' => 'Microwave Oven',
                'sku' => 'HH-MICRO',
                'quantity' => 2,
                'cost_price' => 85000,
                'selling_price' => 120000,
            ],
            [
                'name' => 'Wood Table',
                'sku' => 'HH-TBL-WOOD',
                'quantity' => 1,
                'cost_price' => 65000,
                'selling_price' => 90000,
            ],
            [
                'name' => 'Coffee Table',
                'sku' => 'HH-TBL-COFFEE',
                'quantity' => 3,
                'cost_price' => 45000,
                'selling_price' => 65000,
            ],
            [
                'name' => 'Mini Cooker Electric',
                'sku' => 'HH-COOK-MINI',
                'quantity' => 1,
                'cost_price' => 32000,
                'selling_price' => 45000,
            ],
            [
                'name' => 'Pressure Cooker',
                'sku' => 'HH-COOK-PRES',
                'quantity' => 1,
                'cost_price' => 55000,
                'selling_price' => 75000,
            ],
        ];

        $totalHousehold = 0;
        foreach ($householdProducts as $productData) {
            $totalHousehold += $this->createProduct($shop, $category, $admin, $productData);
        }

        $this->command->info("✓ Seeded {$totalHousehold} Household Items");
    }

    private function createProduct(Shop $shop, Category $category, User $admin, array $data): int
    {
        $isFaulty = $data['is_faulty'] ?? false;
        $quantity = $data['quantity'];

        $product = Product::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'sku' => $data['sku'],
            ],
            [
                'category_id' => $category->id,
                'name' => $data['name'],
                'name_chichewa' => $data['name_chichewa'] ?? $data['name'],
                'description' => $isFaulty ? 'Fault/Damaged unit - for parts or repair' : 'Quality product from Mufash Electronics',
                'barcode' => $data['barcode'] ?? $this->generateBarcode(),
                'cost_price' => $data['cost_price'],
                'selling_price' => $data['selling_price'],
                'quantity' => $quantity,
                'unit' => 'piece',
                'min_stock_level' => max(1, floor($quantity * 0.2)),
                'max_stock_level' => max(10, ceil($quantity * 1.5)),
                'reorder_point' => max(2, floor($quantity * 0.3)),
                'reorder_quantity' => max(5, ceil($quantity * 0.5)),
                'is_vat_applicable' => true,
                'vat_rate' => 16.5,
                'base_currency' => 'MWK',
                'is_active' => ! $isFaulty,
                'created_by' => $admin->id,
            ]
        );

        return $quantity;
    }

    private function generateBarcode(): string
    {
        return '6'.str_pad(rand(0, 99999999999), 12, '0', STR_PAD_LEFT);
    }
}
