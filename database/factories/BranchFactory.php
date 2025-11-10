<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $branchTypes = ['main', 'satellite', 'warehouse', 'kiosk'];

        return [
            'shop_id' => Shop::factory(),
            'name' => fake()->company().' Branch',
            'code' => strtoupper(fake()->lexify('???')).fake()->numberBetween(100, 999),
            'branch_type' => fake()->randomElement($branchTypes),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'district' => fake()->city(),
            'latitude' => fake()->latitude(-15.9, -9.0),
            'longitude' => fake()->longitude(32.0, 36.0),
            'is_active' => true,
            'opened_at' => now()->subMonths(rand(1, 24)),
            'settings' => null,
            'features' => null,
            'created_by' => User::factory(),
        ];
    }

    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_type' => 'main',
            'name' => 'Main Branch',
            'code' => 'MAIN001',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'closed_at' => now()->subDays(rand(1, 30)),
        ]);
    }
}
