<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private string $shopId = 'c5b54d7c-fd57-11f0-9142-9981b81a596e';

    private string $branchId = 'dd9fd602-ff62-11f0-9142-9981b81a596e';

    private array $categories = [
        'TV' => 'b0aeda06-0590-11f1-9142-9981b81a596e',
        'Sound Bars' => 'c03b28dc-0598-11f1-9142-9981b81a596e',
        'TV Accessories' => 'c05c70b4-0598-11f1-9142-9981b81a596e',
        'Home Appliances' => 'c0783aec-0598-11f1-9142-9981b81a596e',
        'Furniture' => 'c096050e-0598-11f1-9142-9981b81a596e',
        'Faulty TVs' => 'c0b46c6a-0598-11f1-9142-9981b81a596e',
    ];

    public function run(): void
    {
        // Disable foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete all existing products
        DB::table('products')->truncate();

        $products = [];

        // Working TVs (71 total)
        $products = array_merge($products, $this->workingTVs());

        // Faulty TVs (9 total)
        $products = array_merge($products, $this->faultyTVs());

        // Sound Bars (38 total)
        $products = array_merge($products, $this->soundBars());

        // TV Accessories & Other Items (67 total)
        $products = array_merge($products, $this->accessories());

        // Insert all products in batches
        collect($products)->chunk(100)->each(function ($chunk) {
            DB::table('products')->insert($chunk->toArray());
        });

        // Re-enable foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Seeded '.count($products).' products successfully.');
    }

    private function generatePrice(int $basePrice): array
    {
        return [
            'cost_price' => $basePrice,
            'landing_cost' => $basePrice,
            'selling_price' => $basePrice,
            'base_currency' => 'MWK',
            'base_currency_price' => $basePrice,
        ];
    }

    private function workingTVs(): array
    {
        $tvCategory = $this->categories['TV'];
        $products = [];

        // 95 inch - 0 units
        // Not added since quantity is 0

        // 75 inch - 1 unit
        $products[] = $this->createProduct('75" TV', 750000, 1, $tvCategory);

        // 60 inch - 2 units
        $products[] = $this->createProduct('60" TV', 600000, 2, $tvCategory);

        // 55 inch - 0 units
        // Not added since quantity is 0

        // 50 smart - 23 units
        $products[] = $this->createProduct('50" Smart TV', 500000, 23, $tvCategory);

        // 50 actual size - 1 unit
        $products[] = $this->createProduct('50" TV (Actual Size)', 500000, 1, $tvCategory);

        // 45 smart - 14 units
        $products[] = $this->createProduct('45" Smart TV', 450000, 14, $tvCategory);

        // 45 normal - 1 unit
        $products[] = $this->createProduct('45" TV', 450000, 1, $tvCategory);

        // 43 inch normal - 3 units
        $products[] = $this->createProduct('43" TV', 430000, 3, $tvCategory);

        // 29 inch - 1 unit
        $products[] = $this->createProduct('29" TV', 290000, 1, $tvCategory);

        // 32 inch smart - 7 units
        $products[] = $this->createProduct('32" Smart TV', 320000, 7, $tvCategory);

        // 32 inch normal - 12 units
        $products[] = $this->createProduct('32" TV', 320000, 12, $tvCategory);

        return $products;
    }

    private function faultyTVs(): array
    {
        $faultyCategory = $this->categories['Faulty TVs'];
        $products = [];

        // 100 smart - 1 unit
        $products[] = $this->createProduct('100" Smart TV (Faulty)', 50000, 1, $faultyCategory);

        // 60 smart - 1 unit
        $products[] = $this->createProduct('60" Smart TV (Faulty)', 40000, 1, $faultyCategory);

        // 50 smart - 2 units
        $products[] = $this->createProduct('50" Smart TV (Faulty)', 30000, 2, $faultyCategory);

        // 45 smart - 2 units
        $products[] = $this->createProduct('45" Smart TV (Faulty)', 25000, 2, $faultyCategory);

        // 43 normal - 1 unit
        $products[] = $this->createProduct('43" TV (Faulty)', 20000, 1, $faultyCategory);

        // 32 smart - 2 units
        $products[] = $this->createProduct('32" Smart TV (Faulty)', 15000, 2, $faultyCategory);

        return $products;
    }

    private function soundBars(): array
    {
        $category = $this->categories['Sound Bars'];
        $products = [];

        $products[] = $this->createProduct('JVC Sound Bar', 50000, 4, $category);
        $products[] = $this->createProduct('JVC Sound Bar (Big)', 70000, 6, $category);
        $products[] = $this->createProduct('JVE Sound Bar', 45000, 1, $category);
        $products[] = $this->createProduct('Qsonic Sound Bar', 40000, 1, $category);
        $products[] = $this->createProduct('Vztec Sound Bar', 35000, 7, $category);
        $products[] = $this->createProduct('Subwoofer L3001', 80000, 4, $category);
        $products[] = $this->createProduct('Sky City Sound Bar', 55000, 3, $category);
        $products[] = $this->createProduct('Bluetooth Speaker Gegi', 60000, 3, $category);
        $products[] = $this->createProduct('Bluetooth Speaker Epe', 60000, 3, $category);
        $products[] = $this->createProduct('Bluetooth Speaker with Stand', 75000, 1, $category);
        $products[] = $this->createProduct('Eagle Soundbar', 65000, 4, $category);
        $products[] = $this->createProduct('Lunar Bluetooth Speaker', 50000, 1, $category);

        return $products;
    }

    private function accessories(): array
    {
        $tvAccessories = $this->categories['TV Accessories'];
        $homeAppliances = $this->categories['Home Appliances'];
        $furniture = $this->categories['Furniture'];
        $products = [];

        // TV Accessories
        $products[] = $this->createProduct('TV Box Smart', 40000, 2, $tvAccessories);
        $products[] = $this->createProduct('HDMI Cable', 5000, 12, $tvAccessories);
        $products[] = $this->createProduct('TV Guard', 8000, 14, $tvAccessories);
        $products[] = $this->createProduct('Flash Drive', 15000, 2, $tvAccessories);
        $products[] = $this->createProduct('Wall Mount (Big)', 25000, 22, $tvAccessories);
        $products[] = $this->createProduct('Wall Mount (Small)', 15000, 6, $tvAccessories);
        $products[] = $this->createProduct('Heavy Mount', 40000, 0, $tvAccessories); // 0 units, adding for catalog
        $products[] = $this->createProduct('GO TV', 35000, 0, $tvAccessories); // 0 units, adding for catalog
        $products[] = $this->createProduct('DSTV', 80000, 6, $tvAccessories);
        $products[] = $this->createProduct('TV Stand (Gold)', 45000, 3, $tvAccessories);
        $products[] = $this->createProduct('Extation', 20000, 1, $tvAccessories);

        // Home Appliances
        $products[] = $this->createProduct('Fan', 35000, 6, $homeAppliances);
        $products[] = $this->createProduct('Min Cooker', 45000, 1, $homeAppliances);
        $products[] = $this->createProduct('Pressure Cooker', 55000, 1, $homeAppliances);
        $products[] = $this->createProduct('Microwave', 85000, 2, $homeAppliances);

        // Furniture
        $products[] = $this->createProduct('Wood Table', 65000, 1, $furniture);
        $products[] = $this->createProduct('Coffee Table', 55000, 3, $furniture);
        $products[] = $this->createProduct('Office Chair', 40000, 6, $furniture);

        return $products;
    }

    private function createProduct(string $name, int $price, int $quantity, string $categoryId): array
    {
        $slug = Str::slug($name);
        $sku = strtoupper(substr($slug, 0, 8)).'-'.rand(1000, 9999);

        return [
            'id' => Str::uuid()->toString(),
            'shop_id' => $this->shopId,
            'branch_id' => $this->branchId,
            'name' => $name,
            'name_chichewa' => null,
            'description' => null,
            'sku' => $sku,
            'barcode' => 'BAR'.rand(10000000, 99999999),
            'manufacturer_code' => null,
            'category_id' => $categoryId,
            'quantity' => $quantity,
            'unit' => 'piece',
            'min_stock_level' => 2,
            'max_stock_level' => null,
            'reorder_point' => 5,
            'reorder_quantity' => null,
            'storage_location' => null,
            'shelf' => null,
            'bin' => null,
            'is_vat_applicable' => true,
            'vat_rate' => 16.50,
            'tax_category' => 'standard',
            'primary_supplier_id' => null,
            'attributes' => null,
            'images' => null,
            'track_batches' => false,
            'track_serial_numbers' => false,
            'has_expiry' => false,
            'total_sold' => 0,
            'total_revenue' => 0,
            'last_sold_at' => null,
            'last_restocked_at' => null,
            'is_active' => $quantity > 0, // Inactive if 0 stock
            'is_deleted' => false,
            'discontinued_at' => null,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
            ...$this->generatePrice($price),
        ];
    }
}
