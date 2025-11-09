<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50000, 5000000);
        $taxAmount = $subtotal * 0.165;
        $freightCost = fake()->randomFloat(2, 0, 100000);
        $insuranceCost = fake()->randomFloat(2, 0, 20000);
        $customsDuty = fake()->randomFloat(2, 0, 200000);
        $clearingFee = fake()->randomFloat(2, 0, 50000);
        $transportCost = fake()->randomFloat(2, 0, 30000);
        $otherCharges = fake()->randomFloat(2, 0, 10000);
        $totalAmount = $subtotal + $taxAmount + $freightCost + $insuranceCost + $customsDuty + $clearingFee + $transportCost + $otherCharges;
        $exchangeRate = fake()->randomFloat(4, 1600, 1700);

        return [
            'shop_id' => null,
            'supplier_id' => null,
            'po_number' => 'PO-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'freight_cost' => $freightCost,
            'insurance_cost' => $insuranceCost,
            'customs_duty' => $customsDuty,
            'clearing_fee' => $clearingFee,
            'transport_cost' => $transportCost,
            'other_charges' => $otherCharges,
            'total_amount' => $totalAmount,
            'currency' => 'MWK',
            'exchange_rate' => $exchangeRate,
            'amount_in_base_currency' => $totalAmount,
            'status' => fake()->randomElement(['draft', 'sent', 'confirmed', 'in_transit', 'at_border', 'clearing', 'received', 'partial', 'cancelled']),
            'order_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'expected_delivery_date' => fake()->dateTimeBetween('now', '+60 days'),
            'actual_delivery_date' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'shipping_method' => fake()->randomElement(['air_freight', 'sea_freight', 'road_transport', 'pickup']),
            'tracking_number' => fake()->optional()->numerify('TRACK-########'),
            'border_point' => fake()->optional()->randomElement(['Mwanza', 'Dedza', 'Songwe', 'Muloza']),
            'clearing_agent_name' => fake()->optional()->company(),
            'clearing_agent_phone' => fake()->optional()->boolean(60) ? '+265'.fake()->numberBetween(111111111, 999999999) : null,
            'customs_entry_number' => fake()->optional()->numerify('CE-########'),
            'documents' => [],
            'notes' => fake()->optional()->sentence(),
            'internal_notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'approved_by' => null,
            'sent_at' => fake()->optional()->dateTimeBetween('-3 months', 'now'),
            'confirmed_at' => fake()->optional()->dateTimeBetween('-2 months', 'now'),
        ];
    }

    /**
     * Indicate that the purchase order is received/completed.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'received',
            'actual_delivery_date' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the purchase order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'actual_delivery_date' => null,
        ]);
    }
}
