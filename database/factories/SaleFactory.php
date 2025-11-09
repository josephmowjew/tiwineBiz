<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 5000, 500000);
        $discountPercentage = fake()->optional()->randomFloat(2, 0, 20);
        $discountAmount = $discountPercentage ? ($subtotal * $discountPercentage / 100) : 0;
        $afterDiscount = $subtotal - $discountAmount;
        $taxAmount = $afterDiscount * 0.165;
        $totalAmount = $afterDiscount + $taxAmount;
        $paymentStatus = fake()->randomElement(['paid', 'partial', 'pending']);
        $amountPaid = $paymentStatus === 'paid' ? $totalAmount : ($paymentStatus === 'partial' ? fake()->randomFloat(2, 1000, $totalAmount - 1000) : 0);
        $balance = $totalAmount - $amountPaid;
        $changeGiven = $paymentStatus === 'paid' ? fake()->optional()->randomFloat(2, 0, 5000) : 0;

        $paymentMethod = fake()->randomElement(['cash', 'airtel_money', 'tnm_mpamba', 'bank_transfer', 'mixed']);

        return [
            'shop_id' => null,
            'sale_number' => 'SALE-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'customer_id' => null,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'discount_percentage' => $discountPercentage,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => $amountPaid,
            'balance' => $balance,
            'change_given' => $changeGiven,
            'payment_status' => $paymentStatus,
            'payment_methods' => $this->getPaymentMethodsData($paymentMethod, $amountPaid),
            'currency' => 'MWK',
            'exchange_rate' => 1.0000,
            'amount_in_base_currency' => $totalAmount,
            'is_fiscalized' => fake()->boolean(60),
            'efd_device_id' => fake()->optional()->numerify('EFD-####'),
            'efd_receipt_number' => fake()->optional()->numerify('########'),
            'efd_qr_code' => fake()->optional()->sha256(),
            'efd_fiscal_signature' => fake()->optional()->sha256(),
            'efd_transmitted_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'efd_response' => [],
            'sale_type' => fake()->randomElement(['regular', 'wholesale', 'retail', 'credit']),
            'notes' => fake()->optional()->sentence(),
            'internal_notes' => fake()->optional()->sentence(),
            'sale_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'completed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'served_by' => null,
            'shift_id' => fake()->optional()->uuid(),
            'cancelled_at' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'refunded_at' => null,
            'refund_amount' => null,
        ];
    }

    /**
     * Get payment methods data.
     */
    protected function getPaymentMethodsData(string $method, float $amount): array
    {
        if ($method === 'mixed') {
            $cashAmount = $amount * 0.6;
            $mobileAmount = $amount - $cashAmount;

            return [
                ['method' => 'cash', 'amount' => $cashAmount],
                ['method' => 'airtel_money', 'amount' => $mobileAmount, 'reference' => fake()->numerify('TXN-########')],
            ];
        }

        $data = ['method' => $method, 'amount' => $amount];
        if (in_array($method, ['airtel_money', 'tnm_mpamba'])) {
            $data['reference'] = fake()->numerify('TXN-########');
            $data['phone'] = '+265'.fake()->numberBetween(111111111, 999999999);
        }

        return [$data];
    }

    /**
     * Indicate that the sale is paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $totalAmount = $attributes['total_amount'];

            return [
                'payment_status' => 'paid',
                'amount_paid' => $totalAmount,
                'balance' => 0,
            ];
        });
    }

    /**
     * Indicate that the sale is partially paid.
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $totalAmount = $attributes['total_amount'];
            $amountPaid = $totalAmount * 0.5;

            return [
                'payment_status' => 'partial',
                'amount_paid' => $amountPaid,
                'balance' => $totalAmount - $amountPaid,
            ];
        });
    }

    /**
     * Indicate that the sale is pending payment.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => 'pending',
                'amount_paid' => 0,
                'balance' => $attributes['total_amount'],
            ];
        });
    }

    /**
     * Indicate that the sale is fiscalized.
     */
    public function fiscalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_fiscalized' => true,
            'efd_device_id' => 'EFD-'.fake()->numerify('####'),
            'efd_receipt_number' => fake()->numerify('########'),
            'efd_qr_code' => fake()->sha256(),
            'efd_fiscal_signature' => fake()->sha256(),
            'efd_transmitted_at' => now(),
        ]);
    }
}
