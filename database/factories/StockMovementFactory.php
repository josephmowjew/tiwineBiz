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
        $movementType = fake()->randomElement([
            'sale', 'purchase', 'return_from_customer', 'return_to_supplier',
            'adjustment_increase', 'adjustment_decrease', 'damage', 'theft',
            'expired', 'transfer_out', 'transfer_in', 'stocktake', 'opening_balance',
        ]);

        $quantity = fake()->randomFloat(3, 1, 100);
        $quantityBefore = fake()->randomFloat(3, 10, 200);

        // Determine if this movement decreases or increases stock
        $decreaseTypes = ['sale', 'return_to_supplier', 'adjustment_decrease', 'damage', 'theft', 'expired', 'transfer_out'];
        $increaseTypes = ['purchase', 'return_from_customer', 'adjustment_increase', 'transfer_in', 'stocktake', 'opening_balance'];

        if (in_array($movementType, $decreaseTypes)) {
            $quantityAfter = $quantityBefore - $quantity;
        } else {
            $quantityAfter = $quantityBefore + $quantity;
        }

        $unitCost = fake()->randomFloat(2, 100, 50000);
        $totalCost = $quantity * $unitCost;

        // Determine reason (required for certain types)
        $reasonRequired = ['adjustment_increase', 'adjustment_decrease', 'damage', 'theft', 'expired', 'stocktake'];
        $reason = in_array($movementType, $reasonRequired)
            ? fake()->sentence()
            : fake()->optional()->sentence();

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
            'reason' => $reason,
            'notes' => fake()->optional()->sentence(),
            'from_location' => fake()->optional()->word(),
            'to_location' => fake()->optional()->word(),
            'created_by' => null,
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
