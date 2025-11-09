<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1000, 500000);
        $paymentMethod = fake()->randomElement(['cash', 'airtel_money', 'tnm_mpamba', 'bank_transfer', 'cheque']);
        $mobileMoneyDetails = null;
        $transactionReference = null;

        if (in_array($paymentMethod, ['airtel_money', 'tnm_mpamba'])) {
            $transactionReference = fake()->numerify('TXN-########');
            $mobileMoneyDetails = [
                'provider' => $paymentMethod,
                'msisdn' => '+265'.fake()->numberBetween(111111111, 999999999),
                'transaction_id' => $transactionReference,
                'sender_name' => fake()->name(),
            ];
        } elseif ($paymentMethod === 'bank_transfer') {
            $transactionReference = fake()->numerify('BT-##########');
        }

        return [
            'shop_id' => null,
            'customer_id' => null,
            'credit_id' => null,
            'sale_id' => null,
            'payment_number' => 'PAY-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'amount' => $amount,
            'currency' => 'MWK',
            'exchange_rate' => 1.0000,
            'amount_in_base_currency' => $amount,
            'payment_method' => $paymentMethod,
            'transaction_reference' => $transactionReference,
            'mobile_money_details' => $mobileMoneyDetails,
            'bank_name' => $paymentMethod === 'bank_transfer' ? fake()->randomElement(['National Bank', 'Standard Bank', 'FDH Bank', 'NBS Bank']) : null,
            'cheque_number' => $paymentMethod === 'cheque' ? fake()->numerify('CHQ-######') : null,
            'cheque_date' => $paymentMethod === 'cheque' ? fake()->dateTimeBetween('-30 days', '+30 days') : null,
            'payment_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'cleared_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional()->sentence(),
            'receipt_sent' => fake()->boolean(70),
            'receipt_sent_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'received_by' => null,
        ];
    }

    /**
     * Indicate that the payment is via mobile money.
     */
    public function mobileMoney(): static
    {
        $provider = fake()->randomElement(['airtel_money', 'tnm_mpamba']);

        return $this->state(fn (array $attributes) => [
            'payment_method' => $provider,
            'transaction_reference' => fake()->numerify('TXN-########'),
            'mobile_money_details' => [
                'provider' => $provider,
                'msisdn' => '+265'.fake()->numberBetween(111111111, 999999999),
                'transaction_id' => fake()->numerify('TXN-########'),
                'sender_name' => fake()->name(),
            ],
        ]);
    }

    /**
     * Indicate that the payment is via cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
            'transaction_reference' => null,
            'mobile_money_details' => null,
        ]);
    }
}
