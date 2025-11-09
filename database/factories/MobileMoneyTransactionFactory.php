<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MobileMoneyTransaction>
 */
class MobileMoneyTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = fake()->randomElement(['airtel_money', 'tnm_mpamba']);
        $transactionType = fake()->randomElement(['payment', 'refund', 'payout']);
        $amount = fake()->randomFloat(2, 1000, 500000);
        $transactionFee = $amount * 0.01;

        return [
            'shop_id' => null,
            'provider' => $provider,
            'transaction_id' => fake()->unique()->numerify('TXN-'.strtoupper(substr($provider, 0, 3)).'-########'),
            'transaction_type' => $transactionType,
            'msisdn' => '+265'.fake()->numberBetween(111111111, 999999999),
            'sender_name' => fake()->name(),
            'receiver_name' => fake()->name(),
            'amount' => $amount,
            'currency' => 'MWK',
            'transaction_fee' => $transactionFee,
            'reference_type' => fake()->optional()->randomElement(['sale', 'payment', 'subscription_payment']),
            'reference_id' => fake()->optional()->uuid(),
            'status' => fake()->randomElement(['pending', 'completed', 'failed', 'cancelled']),
            'request_payload' => [
                'amount' => $amount,
                'msisdn' => '+265'.fake()->numberBetween(111111111, 999999999),
                'reference' => fake()->numerify('REF-######'),
            ],
            'response_payload' => [
                'transaction_id' => fake()->numerify('TXN-########'),
                'status' => 'success',
                'message' => 'Transaction successful',
            ],
            'webhook_received_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'webhook_payload' => [],
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'confirmed_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the transaction is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'confirmed_at' => null,
        ]);
    }
}
