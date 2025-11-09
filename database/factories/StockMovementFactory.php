<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $movementType = fake()->randomElement(['sale', 'purchase', 'adjustment', 'return', 'transfer', 'damage', 'expired']);
        $quantity = fake()->randomFloat(3, 1, 100);
        $quantityBefore = fake()->randomFloat(3, 0, 200);
        $quantityAfter = $movementType === 'sale' ? $quantityBefore - $quantity : $quantityBefore + $quantity;
        $unitCost = fake()->randomFloat(2, 100, 50000);
        $totalCost = $quantity * $unitCost;

        return [
            'shop_id' => null,
            'product_id' => null,
            'batch_id' => null,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'reference_type' => fake()->optional()->randomElement(['sale', 'purchase_order', 'adjustment']),
            'reference_id' => fake()->optional()->uuid(),
            'reason' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'from_location' => fake()->optional()->word(),
            'to_location' => fake()->optional()->word(),
            'created_by' => null,
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
