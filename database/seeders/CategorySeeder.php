<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates system-wide categories (shop_id = null) for common Malawian retail businesses.
     * These categories are available to all shops and can be used as templates.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Electronics - Main category
            $electronics = Category::create([
                'shop_id' => null,
                'name' => 'Electronics',
                'name_chichewa' => 'Zida Zamagetsi',
                'slug' => 'electronics',
                'description' => 'Electronic devices and accessories',
                'depth' => 0,
                'display_order' => 1,
                'is_active' => true,
            ]);

            // Electronics subcategories
            Category::create([
                'shop_id' => null,
                'parent_id' => $electronics->id,
                'name' => 'Mobile Phones',
                'name_chichewa' => 'Mafoni',
                'slug' => 'mobile-phones',
                'description' => 'Smartphones and feature phones',
                'depth' => 1,
                'display_order' => 1,
                'is_active' => true,
            ]);

            Category::create([
                'shop_id' => null,
                'parent_id' => $electronics->id,
                'name' => 'Laptops',
                'name_chichewa' => 'Ma Laptop',
                'slug' => 'laptops',
                'description' => 'Laptop computers',
                'depth' => 1,
                'display_order' => 2,
                'is_active' => true,
            ]);

            Category::create([
                'shop_id' => null,
                'parent_id' => $electronics->id,
                'name' => 'Accessories',
                'name_chichewa' => 'Zida Zothandizira',
                'slug' => 'electronics-accessories',
                'description' => 'Chargers, cables, cases, and other accessories',
                'depth' => 1,
                'display_order' => 3,
                'is_active' => true,
            ]);

            // Spare Parts - Main category
            $spareParts = Category::create([
                'shop_id' => null,
                'name' => 'Spare Parts',
                'name_chichewa' => 'Zida Zosinthira',
                'slug' => 'spare-parts',
                'description' => 'Vehicle and machinery spare parts',
                'depth' => 0,
                'display_order' => 2,
                'is_active' => true,
            ]);

            // Spare Parts subcategories
            Category::create([
                'shop_id' => null,
                'parent_id' => $spareParts->id,
                'name' => 'Engine Parts',
                'name_chichewa' => 'Zida Za Injini',
                'slug' => 'engine-parts',
                'description' => 'Engine components and parts',
                'depth' => 1,
                'display_order' => 1,
                'is_active' => true,
            ]);

            Category::create([
                'shop_id' => null,
                'parent_id' => $spareParts->id,
                'name' => 'Body Parts',
                'name_chichewa' => 'Zida Za Thupi',
                'slug' => 'body-parts',
                'description' => 'Body panels and exterior parts',
                'depth' => 1,
                'display_order' => 2,
                'is_active' => true,
            ]);

            // Hardware - Main category
            Category::create([
                'shop_id' => null,
                'name' => 'Hardware',
                'name_chichewa' => 'Zida Zomangira',
                'slug' => 'hardware',
                'description' => 'Building and construction materials',
                'depth' => 0,
                'display_order' => 3,
                'is_active' => true,
            ]);

            // Groceries - Main category
            $groceries = Category::create([
                'shop_id' => null,
                'name' => 'Groceries',
                'name_chichewa' => 'Zakudya',
                'slug' => 'groceries',
                'description' => 'Food items and household supplies',
                'depth' => 0,
                'display_order' => 4,
                'is_active' => true,
            ]);

            // Groceries subcategories
            Category::create([
                'shop_id' => null,
                'parent_id' => $groceries->id,
                'name' => 'Beverages',
                'name_chichewa' => 'Zakumwa',
                'slug' => 'beverages',
                'description' => 'Drinks and beverages',
                'depth' => 1,
                'display_order' => 1,
                'is_active' => true,
            ]);

            Category::create([
                'shop_id' => null,
                'parent_id' => $groceries->id,
                'name' => 'Snacks',
                'name_chichewa' => 'Zokoma',
                'slug' => 'snacks',
                'description' => 'Snacks and quick bites',
                'depth' => 1,
                'display_order' => 2,
                'is_active' => true,
            ]);

            // Pharmacy - Main category
            Category::create([
                'shop_id' => null,
                'name' => 'Pharmacy',
                'name_chichewa' => 'Mankhwala',
                'slug' => 'pharmacy',
                'description' => 'Medical supplies and medicines',
                'depth' => 0,
                'display_order' => 5,
                'is_active' => true,
            ]);

            // Clothing - Main category
            $clothing = Category::create([
                'shop_id' => null,
                'name' => 'Clothing',
                'name_chichewa' => 'Zovala',
                'slug' => 'clothing',
                'description' => 'Clothes and fashion items',
                'depth' => 0,
                'display_order' => 6,
                'is_active' => true,
            ]);

            // Clothing subcategories
            Category::create([
                'shop_id' => null,
                'parent_id' => $clothing->id,
                'name' => 'Men\'s Wear',
                'name_chichewa' => 'Zovala Za Amuna',
                'slug' => 'mens-wear',
                'description' => 'Men\'s clothing',
                'depth' => 1,
                'display_order' => 1,
                'is_active' => true,
            ]);

            Category::create([
                'shop_id' => null,
                'parent_id' => $clothing->id,
                'name' => 'Women\'s Wear',
                'name_chichewa' => 'Zovala Za Akazi',
                'slug' => 'womens-wear',
                'description' => 'Women\'s clothing',
                'depth' => 1,
                'display_order' => 2,
                'is_active' => true,
            ]);

            Category::create([
                'shop_id' => null,
                'parent_id' => $clothing->id,
                'name' => 'Children\'s Wear',
                'name_chichewa' => 'Zovala Za Ana',
                'slug' => 'childrens-wear',
                'description' => 'Children\'s clothing',
                'depth' => 1,
                'display_order' => 3,
                'is_active' => true,
            ]);

            // Household Items - Main category
            Category::create([
                'shop_id' => null,
                'name' => 'Household Items',
                'name_chichewa' => 'Zinthu Zanyumba',
                'slug' => 'household-items',
                'description' => 'Home and kitchen items',
                'depth' => 0,
                'display_order' => 7,
                'is_active' => true,
            ]);

            // Stationery - Main category
            Category::create([
                'shop_id' => null,
                'name' => 'Stationery',
                'name_chichewa' => 'Zida Zolemba',
                'slug' => 'stationery',
                'description' => 'Office and school supplies',
                'depth' => 0,
                'display_order' => 8,
                'is_active' => true,
            ]);
        });
    }
}
