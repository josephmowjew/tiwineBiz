<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductBatch>
 */
class ProductBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $initialQty = fake()->randomFloat(3, 10, 1000);
        $remainingQty = fake()->randomFloat(3, 0, $initialQty);
        $unitCost = fake()->randomFloat(2, 100, 10000);
        $productCost = $initialQty * $unitCost;
        $freightCost = fake()->randomFloat(2, 0, 50000);
        $customsDuty = fake()->randomFloat(2, 0, 30000);
        $clearingFee = fake()->randomFloat(2, 0, 10000);
        $otherCosts = fake()->randomFloat(2, 0, 5000);
        $totalLandedCost = $productCost + $freightCost + $customsDuty + $clearingFee + $otherCosts;

        return [
            'product_id' => null,
            'purchase_order_id' => null,
            'supplier_id' => null,
            'batch_number' => 'BATCH-'.fake()->unique()->numerify('######'),
            'lot_number' => fake()->optional()->numerify('LOT-####'),
            'initial_quantity' => $initialQty,
            'remaining_quantity' => $remainingQty,
            'unit_cost' => $unitCost,
            'currency' => 'MWK',
            'product_cost' => $productCost,
            'freight_cost' => $freightCost,
            'customs_duty' => $customsDuty,
            'clearing_fee' => $clearingFee,
            'other_costs' => $otherCosts,
            'total_landed_cost' => $totalLandedCost,
            'purchase_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'manufacture_date' => fake()->optional()->dateTimeBetween('-1 year', '-1 month'),
            'expiry_date' => fake()->optional()->dateTimeBetween('+6 months', '+3 years'),
            'is_depleted' => $remainingQty == 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the batch is depleted.
     */
    public function depleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'remaining_quantity' => 0,
            'is_depleted' => true,
        ]);
    }

    /**
     * Indicate that the batch is near expiry.
     */
    public function nearExpiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }
}
