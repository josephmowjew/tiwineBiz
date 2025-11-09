<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $creditLimit = fake()->randomFloat(2, 0, 500000);
        $currentBalance = fake()->randomFloat(2, 0, $creditLimit);

        return [
            'shop_id' => null,
            'customer_number' => 'CUST-'.fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'phone' => '+265'.fake()->numberBetween(111111111, 999999999),
            'email' => fake()->optional()->safeEmail(),
            'whatsapp_number' => fake()->optional()->boolean(70) ? '+265'.fake()->numberBetween(111111111, 999999999) : null,
            'physical_address' => fake()->optional()->streetAddress(),
            'city' => fake()->randomElement(['Blantyre', 'Lilongwe', 'Mzuzu', 'Zomba', 'Mangochi']),
            'district' => fake()->randomElement(['Blantyre', 'Lilongwe', 'Mzuzu', 'Zomba', 'Mangochi', 'Karonga', 'Kasungu', 'Dedza']),
            'credit_limit' => $creditLimit,
            'current_balance' => $currentBalance,
            'total_spent' => fake()->randomFloat(2, 0, 1000000),
            'total_credit_issued' => fake()->randomFloat(2, 0, $creditLimit),
            'total_credit_collected' => fake()->randomFloat(2, 0, $creditLimit),
            'trust_level' => fake()->randomElement(['trusted', 'monitor', 'restricted', 'new']),
            'payment_behavior_score' => fake()->numberBetween(0, 100),
            'purchase_count' => fake()->numberBetween(0, 500),
            'last_purchase_date' => fake()->optional()->dateTimeBetween('-6 months', 'now'),
            'average_purchase_value' => fake()->randomFloat(2, 1000, 50000),
            'preferred_language' => fake()->randomElement(['en', 'ny']),
            'preferred_contact_method' => fake()->randomElement(['phone', 'whatsapp', 'email', 'sms']),
            'notes' => fake()->optional()->sentence(),
            'tags' => fake()->optional()->randomElements(['vip', 'wholesale', 'retail', 'regular'], fake()->numberBetween(0, 2)),
            'is_active' => true,
            'blocked_at' => null,
            'block_reason' => null,
            'created_by' => null,
        ];
    }

    /**
     * Indicate that the customer has credit.
     */
    public function withCredit(): static
    {
        $creditLimit = fake()->randomFloat(2, 100000, 500000);

        return $this->state(fn (array $attributes) => [
            'credit_limit' => $creditLimit,
            'current_balance' => fake()->randomFloat(2, 50000, $creditLimit),
            'trust_level' => 'trusted',
        ]);
    }

    /**
     * Indicate that the customer is trusted.
     */
    public function trusted(): static
    {
        return $this->state(fn (array $attributes) => [
            'trust_level' => 'trusted',
            'payment_behavior_score' => fake()->numberBetween(80, 100),
            'tags' => ['vip', 'regular'],
        ]);
    }

    /**
     * Indicate that the customer is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'blocked_at' => now(),
            'block_reason' => fake()->randomElement(['Overdue payments', 'Fraudulent activity', 'Repeated late payments']),
            'trust_level' => 'restricted',
        ]);
    }
}
