<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShopUser>
 */
class ShopUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => null,
            'user_id' => null,
            'role_id' => null,
            'is_active' => true,
            'invited_by' => null,
            'invitation_token' => null,
            'invitation_expires_at' => null,
            'invitation_accepted_at' => now(),
            'joined_at' => now(),
            'last_accessed_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Indicate that the user is pending invitation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'invitation_token' => Str::random(32),
            'invitation_expires_at' => now()->addDays(7),
            'invitation_accepted_at' => null,
            'joined_at' => null,
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
