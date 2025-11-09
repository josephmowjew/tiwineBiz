<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $officialRate = fake()->randomFloat(4, 1600, 1700);
        $streetRate = $officialRate * fake()->randomFloat(2, 1.02, 1.08);

        return [
            'base_currency' => 'MWK',
            'target_currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'ZAR']),
            'official_rate' => $officialRate,
            'street_rate' => $streetRate,
            'rate_used' => fake()->randomElement([$officialRate, $streetRate]),
            'effective_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'valid_until' => fake()->dateTimeBetween('now', '+30 days'),
            'source' => fake()->randomElement(['RBM', 'manual', 'API', 'street_market']),
            'created_by' => null,
        ];
    }
}
