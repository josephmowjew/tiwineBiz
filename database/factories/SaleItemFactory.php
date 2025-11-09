<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 50);
        $unitCost = fake()->randomFloat(2, 100, 10000);
        $unitPrice = fake()->randomFloat(2, 500, 50000);
        $discountPercentage = fake()->optional()->randomFloat(2, 0, 15);
        $discountAmount = $discountPercentage ? (($quantity * $unitPrice) * $discountPercentage / 100) : 0;
        $subtotal = ($quantity * $unitPrice) - $discountAmount;
        $isTaxable = fake()->boolean(70);
        $taxRate = $isTaxable ? 16.5 : 0;
        $taxAmount = $isTaxable ? ($subtotal * ($taxRate / 100)) : 0;
        $total = $subtotal + $taxAmount;

        return [
            'sale_id' => null,
            'product_id' => null,
            'batch_id' => null,
            'product_name' => fake()->words(3, true),
            'product_name_chichewa' => fake()->optional()->word(),
            'product_sku' => fake()->bothify('??-###'),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['piece', 'box', 'pack', 'kg', 'liter']),
            'unit_cost' => $unitCost,
            'unit_price' => $unitPrice,
            'discount_amount' => $discountAmount,
            'discount_percentage' => $discountPercentage,
            'is_taxable' => $isTaxable,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'total' => $total,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
