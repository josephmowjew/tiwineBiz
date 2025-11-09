<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qtyOrdered = fake()->randomFloat(3, 10, 500);
        $qtyReceived = fake()->randomFloat(3, 0, $qtyOrdered);
        $unitPrice = fake()->randomFloat(2, 100, 50000);
        $subtotal = $qtyOrdered * $unitPrice;

        return [
            'purchase_order_id' => null,
            'product_id' => null,
            'product_name' => fake()->words(3, true),
            'product_code' => fake()->bothify('??-###'),
            'quantity_ordered' => $qtyOrdered,
            'quantity_received' => $qtyReceived,
            'unit' => fake()->randomElement(['piece', 'box', 'pack', 'kg', 'liter']),
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'is_complete' => $qtyReceived >= $qtyOrdered,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
