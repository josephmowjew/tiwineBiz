<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credit>
 */
class CreditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalAmount = fake()->randomFloat(2, 5000, 500000);
        $amountPaid = fake()->randomFloat(2, 0, $originalAmount);
        $balance = $originalAmount - $amountPaid;
        $issueDate = fake()->dateTimeBetween('-6 months', 'now');
        $paymentTerm = fake()->randomElement(['lero', 'mawa', 'sabata_imodzi', 'masabata_awiri', 'mwezi_umodzi', 'miyezi_iwiri', 'miyezi_itatu']);
        $daysToAdd = $this->getDaysForPaymentTerm($paymentTerm);
        $dueDate = (clone $issueDate)->modify("+{$daysToAdd} days");

        $status = $balance == 0 ? 'paid' : ($amountPaid > 0 ? 'partial' : ($dueDate < now() ? 'overdue' : 'pending'));

        return [
            'shop_id' => null,
            'customer_id' => null,
            'sale_id' => null,
            'credit_number' => 'CREDIT-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'original_amount' => $originalAmount,
            'amount_paid' => $amountPaid,
            'balance' => $balance,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'payment_term' => $paymentTerm,
            'status' => $status,
            'last_reminder_sent_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'reminder_count' => fake()->numberBetween(0, 5),
            'next_reminder_date' => fake()->optional()->dateTimeBetween('now', '+7 days'),
            'collection_attempts' => fake()->numberBetween(0, 10),
            'last_collection_attempt_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'escalation_level' => fake()->numberBetween(0, 3),
            'notes' => fake()->optional()->sentence(),
            'internal_notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'paid_at' => $balance == 0 ? now() : null,
            'written_off_at' => null,
            'written_off_by' => null,
            'write_off_reason' => null,
        ];
    }

    /**
     * Get days for payment term.
     */
    protected function getDaysForPaymentTerm(string $term): int
    {
        return match ($term) {
            'lero' => 0,
            'mawa' => 1,
            'sabata_imodzi' => 7,
            'masabata_awiri' => 14,
            'mwezi_umodzi' => 30,
            'miyezi_iwiri' => 60,
            'miyezi_itatu' => 90,
            default => 30,
        };
    }

    /**
     * Indicate that the credit is overdue.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'overdue',
                'due_date' => fake()->dateTimeBetween('-60 days', '-1 day'),
                'escalation_level' => fake()->numberBetween(1, 3),
                'reminder_count' => fake()->numberBetween(2, 10),
            ];
        });
    }

    /**
     * Indicate that the credit is partially paid.
     */
    public function partial(): static
    {
        return $this->state(function (array $attributes) {
            $originalAmount = $attributes['original_amount'];
            $amountPaid = $originalAmount * 0.5;

            return [
                'amount_paid' => $amountPaid,
                'balance' => $originalAmount - $amountPaid,
                'status' => 'partial',
            ];
        });
    }

    /**
     * Indicate that the credit is fully paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'amount_paid' => $attributes['original_amount'],
                'balance' => 0,
                'status' => 'paid',
                'paid_at' => now(),
            ];
        });
    }
}
