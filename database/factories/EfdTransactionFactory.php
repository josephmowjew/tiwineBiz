<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EfdTransaction>
 */
class EfdTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalAmount = fake()->randomFloat(2, 5000, 500000);
        $vatAmount = $totalAmount * 0.165;

        return [
            'shop_id' => null,
            'efd_device_id' => 'EFD-'.fake()->numerify('####'),
            'efd_device_serial' => fake()->numerify('SN-##########'),
            'sale_id' => null,
            'fiscal_receipt_number' => fake()->numerify('########'),
            'fiscal_day_counter' => fake()->numberBetween(1, 9999),
            'fiscal_signature' => fake()->sha256(),
            'qr_code_data' => fake()->sha256(),
            'verification_url' => 'https://verify.mra.mw/'.fake()->numerify('########'),
            'total_amount' => $totalAmount,
            'vat_amount' => $vatAmount,
            'mra_response_code' => fake()->randomElement(['200', '201', 'ACK']),
            'mra_response_message' => 'Transaction successful',
            'mra_acknowledgement' => [
                'status' => 'success',
                'timestamp' => now()->toIso8601String(),
                'receipt_number' => fake()->numerify('########'),
            ],
            'transmitted_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'transmission_status' => fake()->randomElement(['success', 'pending', 'failed']),
            'retry_count' => fake()->numberBetween(0, 3),
            'last_retry_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'next_retry_at' => fake()->optional()->dateTimeBetween('now', '+1 hour'),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the transaction was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'transmission_status' => 'success',
            'mra_response_code' => '200',
            'retry_count' => 0,
            'next_retry_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'transmission_status' => 'failed',
            'mra_response_code' => '500',
            'mra_response_message' => 'Connection timeout',
            'retry_count' => 3,
            'next_retry_at' => now()->addHour(),
        ]);
    }
}
