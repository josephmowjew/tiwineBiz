<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => null,
            'name' => fake()->company(),
            'legal_name' => fake()->optional()->company(),
            'supplier_code' => 'SUP-'.fake()->unique()->numerify('####'),
            'contact_person' => fake()->name(),
            'contact_phone' => '+265'.fake()->numberBetween(111111111, 999999999),
            'contact_email' => fake()->optional()->email(),
            'phone' => '+265'.fake()->numberBetween(111111111, 999999999),
            'email' => fake()->optional()->companyEmail(),
            'website' => fake()->optional()->url(),
            'physical_address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Blantyre', 'Lilongwe', 'Mzuzu', 'Zomba', 'Mangochi']),
            'country' => fake()->randomElement(['Malawi', 'South Africa', 'Tanzania', 'Mozambique', 'China']),
            'payment_terms' => fake()->randomElement(['cash_on_delivery', 'net_30', 'net_60', '50_50', 'advance_payment']),
            'credit_days' => fake()->randomElement([0, 7, 14, 30, 60, 90]),
            'bank_account_name' => fake()->optional()->company(),
            'bank_account_number' => fake()->optional()->numerify('##########'),
            'bank_name' => fake()->optional()->randomElement(['National Bank', 'Standard Bank', 'FDH Bank', 'NBS Bank']),
            'tax_id' => fake()->optional()->numerify('##########'),
            'total_orders' => fake()->numberBetween(0, 100),
            'total_order_value' => fake()->randomFloat(2, 0, 5000000),
            'average_delivery_days' => fake()->numberBetween(1, 30),
            'reliability_score' => fake()->numberBetween(1, 100),
            'last_order_date' => fake()->optional()->dateTimeBetween('-6 months', 'now'),
            'is_active' => true,
            'is_preferred' => fake()->boolean(20),
            'notes' => fake()->optional()->sentence(),
            'tags' => fake()->optional()->randomElements(['reliable', 'expensive', 'fast_delivery', 'quality_products'], fake()->numberBetween(0, 3)),
        ];
    }

    /**
     * Indicate that the supplier is preferred.
     */
    public function preferred(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_preferred' => true,
            'reliability_score' => fake()->numberBetween(80, 100),
        ]);
    }

    /**
     * Indicate that the supplier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
