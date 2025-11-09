<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 15000, 75000);
        $periodStart = fake()->dateTimeBetween('-60 days', 'now');
        $periodEnd = (clone $periodStart)->modify('+30 days');

        return [
            'subscription_id' => null,
            'shop_id' => null,
            'payment_number' => 'SUBPAY-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'amount' => $amount,
            'currency' => 'MWK',
            'payment_method' => fake()->randomElement(['airtel_money', 'tnm_mpamba', 'bank_transfer', 'cash']),
            'transaction_reference' => fake()->numerify('TXN-########'),
            'status' => fake()->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payment_date' => fake()->dateTimeBetween('-60 days', 'now'),
            'confirmed_at' => fake()->optional()->dateTimeBetween('-60 days', 'now'),
            'confirmed_by' => null,
        ];
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'confirmed_at' => null,
        ]);
    }
}
