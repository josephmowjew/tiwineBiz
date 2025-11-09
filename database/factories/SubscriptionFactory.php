<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = fake()->randomElement(['free', 'business', 'professional', 'enterprise']);
        $billingCycle = fake()->randomElement(['monthly', 'quarterly', 'yearly']);
        $amount = $this->getAmountForPlan($plan, $billingCycle);
        $startedAt = fake()->dateTimeBetween('-1 year', 'now');
        $periodStart = fake()->dateTimeBetween('-30 days', 'now');
        $periodEnd = fake()->dateTimeBetween('now', '+60 days');

        return [
            'shop_id' => null,
            'plan' => $plan,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'currency' => 'MWK',
            'status' => fake()->randomElement(['active', 'trialing', 'past_due', 'cancelled', 'expired']),
            'started_at' => $startedAt,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'cancel_at_period_end' => false,
            'trial_ends_at' => null,
            'features' => $this->getFeaturesForPlan($plan),
            'limits' => $this->getLimitsForPlan($plan),
        ];
    }

    /**
     * Get amount based on plan and billing cycle.
     */
    protected function getAmountForPlan(string $plan, string $cycle): float
    {
        $monthly = [
            'free' => 0,
            'business' => 15000,
            'professional' => 35000,
            'enterprise' => 75000,
        ];

        $multiplier = match ($cycle) {
            'monthly' => 1,
            'quarterly' => 2.7,
            'yearly' => 10,
            default => 1,
        };

        return $monthly[$plan] * $multiplier;
    }

    /**
     * Get features for plan.
     */
    protected function getFeaturesForPlan(string $plan): array
    {
        $features = [
            'free' => ['basic_pos', 'inventory_management'],
            'business' => ['basic_pos', 'inventory_management', 'customer_management', 'basic_reports'],
            'professional' => ['basic_pos', 'inventory_management', 'customer_management', 'advanced_reports', 'mobile_money', 'multi_currency'],
            'enterprise' => ['basic_pos', 'inventory_management', 'customer_management', 'advanced_reports', 'mobile_money', 'multi_currency', 'efd_integration', 'api_access', 'multi_location'],
        ];

        return $features[$plan] ?? $features['free'];
    }

    /**
     * Get limits for plan.
     */
    protected function getLimitsForPlan(string $plan): array
    {
        $limits = [
            'free' => ['products' => 100, 'users' => 1, 'monthly_sales' => 500],
            'business' => ['products' => 1000, 'users' => 5, 'monthly_sales' => 5000],
            'professional' => ['products' => 10000, 'users' => 15, 'monthly_sales' => 50000],
            'enterprise' => ['products' => -1, 'users' => -1, 'monthly_sales' => -1],
        ];

        return $limits[$plan] ?? $limits['free'];
    }

    /**
     * Indicate that the subscription is on trial.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => fake()->randomElement(['Too expensive', 'Not enough features', 'Switching to competitor']),
        ]);
    }
}
