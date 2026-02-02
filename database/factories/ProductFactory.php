<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $costPrice = fake()->randomFloat(2, 100, 50000);
        $sellingPrice = $costPrice * fake()->randomFloat(2, 1.2, 2.0);
        $quantity = fake()->randomFloat(3, 0, 100);

        return [
            'shop_id' => null,
            'name' => fake()->words(3, true),
            'name_chichewa' => fake()->optional()->word(),
            'description' => fake()->optional()->sentence(),
            'sku' => strtoupper(fake()->unique()->bothify('??###??')),
            'barcode' => fake()->optional()->ean13(),
            'manufacturer_code' => fake()->optional()->numerify('MFG-####'),
            'category_id' => null,
            'cost_price' => $costPrice,
            'selling_price' => $sellingPrice,
            'base_currency' => 'MWK',
            'base_currency_price' => null,
            'last_exchange_rate_snapshot' => null,
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['piece', 'kg', 'g', 'liter', 'ml', 'meter', 'cm', 'box', 'dozen']),
            'min_stock_level' => fake()->randomFloat(3, 5, 20),
            'max_stock_level' => fake()->randomFloat(3, 50, 200),
            'reorder_point' => fake()->randomFloat(3, 10, 30),
            'reorder_quantity' => fake()->randomFloat(3, 20, 100),
            'storage_location' => fake()->optional()->word(),
            'shelf' => fake()->optional()->numerify('S-##'),
            'bin' => fake()->optional()->numerify('B-##'),
            'is_vat_applicable' => fake()->boolean(70),
            'vat_rate' => fake()->randomElement([0, 16.5]),
            'tax_category' => fake()->randomElement(['standard', 'zero_rated', 'exempt']),
            'primary_supplier_id' => null,
            'attributes' => [],
            'images' => [],
            'track_batches' => fake()->boolean(30),
            'track_serial_numbers' => fake()->boolean(10),
            'has_expiry' => fake()->boolean(20),
            'total_sold' => fake()->randomFloat(3, 0, 1000),
            'total_revenue' => fake()->randomFloat(2, 0, 500000),
            'last_sold_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'last_restocked_at' => fake()->optional()->dateTimeBetween('-60 days', 'now'),
            'is_active' => true,
            'discontinued_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'discontinued_at' => null,
        ]);
    }

    /**
     * Indicate that the product has low stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->randomFloat(3, 1, 5),
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product tracks batches.
     */
    public function withBatch(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_batches' => true,
            'has_expiry' => fake()->boolean(70),
        ]);
    }
}
