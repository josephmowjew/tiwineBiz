<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+265'.fake()->numberBetween(111111111, 999999999),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'preferred_language' => fake()->randomElement(['en', 'ny']),
            'timezone' => 'Africa/Blantyre',
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'is_active' => true,
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days'),
            'last_login_ip' => fake()->optional()->ipv4(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model's email is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);
    }

    /**
     * Indicate that the account is locked.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'locked_until' => now()->addHours(24),
            'failed_login_attempts' => 5,
        ]);
    }

    /**
     * Indicate that the user has 2FA enabled.
     */
    public function with2FA(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt(Str::random(32)),
        ]);
    }
}
