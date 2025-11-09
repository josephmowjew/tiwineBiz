<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessType = fake()->randomElement(['spare_parts', 'electronics', 'hardware', 'pharmacy', 'general_store', 'grocery', 'clothing']);
        $city = fake()->randomElement(['Blantyre', 'Lilongwe', 'Mzuzu', 'Zomba', 'Mangochi']);
        $district = fake()->randomElement(['Blantyre', 'Lilongwe', 'Mzuzu', 'Zomba', 'Mangochi', 'Karonga', 'Kasungu', 'Dedza']);
        $tier = fake()->randomElement(['free', 'business', 'professional', 'enterprise']);

        return [
            'owner_id' => null,
            'name' => fake()->company(),
            'business_type' => $businessType,
            'legal_name' => fake()->optional()->company(),
            'registration_number' => fake()->optional()->numerify('REG-########'),
            'tpin' => fake()->numerify('##########'),
            'vrn' => fake()->optional()->numerify('VRN-########'),
            'is_vat_registered' => fake()->boolean(30),
            'phone' => '+265'.fake()->numberBetween(111111111, 999999999),
            'email' => fake()->optional()->companyEmail(),
            'website' => fake()->optional()->url(),
            'address' => fake()->streetAddress(),
            'city' => $city,
            'district' => $district,
            'country' => 'Malawi',
            'latitude' => fake()->latitude(-17, -9),
            'longitude' => fake()->longitude(32, 36),
            'logo_url' => null,
            'primary_color' => fake()->hexColor(),
            'default_currency' => 'MWK',
            'fiscal_year_start_month' => fake()->numberBetween(1, 12),
            'subscription_tier' => $tier,
            'subscription_status' => 'active',
            'subscription_started_at' => now()->subMonths(fake()->numberBetween(1, 12)),
            'subscription_expires_at' => now()->addMonths(fake()->numberBetween(1, 12)),
            'trial_ends_at' => null,
            'features' => $this->getFeaturesForTier($tier),
            'limits' => $this->getLimitsForTier($tier),
            'settings' => [
                'receipt_footer' => 'Thank you for your business',
                'print_receipt_automatically' => true,
                'low_stock_notification' => true,
                'currency_display' => 'MWK',
            ],
            'is_active' => true,
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ];
    }

    /**
     * Get features based on subscription tier.
     */
    protected function getFeaturesForTier(string $tier): array
    {
        $features = [
            'free' => ['basic_pos', 'inventory_management'],
            'business' => ['basic_pos', 'inventory_management', 'customer_management', 'basic_reports'],
            'professional' => ['basic_pos', 'inventory_management', 'customer_management', 'advanced_reports', 'mobile_money', 'multi_currency'],
            'enterprise' => ['basic_pos', 'inventory_management', 'customer_management', 'advanced_reports', 'mobile_money', 'multi_currency', 'efd_integration', 'api_access', 'multi_location'],
        ];

        return $features[$tier] ?? $features['free'];
    }

    /**
     * Get limits based on subscription tier.
     */
    protected function getLimitsForTier(string $tier): array
    {
        $limits = [
            'free' => ['products' => 100, 'users' => 1, 'monthly_sales' => 500],
            'business' => ['products' => 1000, 'users' => 5, 'monthly_sales' => 5000],
            'professional' => ['products' => 10000, 'users' => 15, 'monthly_sales' => 50000],
            'enterprise' => ['products' => -1, 'users' => -1, 'monthly_sales' => -1],
        ];

        return $limits[$tier] ?? $limits['free'];
    }

    /**
     * Indicate that the shop is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'subscription_status' => 'active',
            'deactivated_at' => null,
        ]);
    }

    /**
     * Indicate that the shop is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'subscription_status' => 'suspended',
            'deactivated_at' => now(),
            'deactivation_reason' => 'Payment overdue',
        ]);
    }

    /**
     * Indicate that the shop is on trial.
     */
    public function withTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'professional',
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
            'subscription_started_at' => now(),
            'subscription_expires_at' => now()->addDays(14),
        ]);
    }
}
