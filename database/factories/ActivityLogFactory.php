<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement(['created', 'updated', 'deleted', 'viewed', 'exported', 'imported']);
        $entityType = fake()->randomElement(['Product', 'Sale', 'Customer', 'PurchaseOrder', 'Payment', 'User']);

        return [
            'shop_id' => null,
            'user_id' => null,
            'user_name' => fake()->name(),
            'user_email' => fake()->safeEmail(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => fake()->uuid(),
            'entity_name' => fake()->words(3, true),
            'old_values' => $action === 'updated' ? ['name' => fake()->word(), 'status' => 'draft'] : null,
            'new_values' => in_array($action, ['created', 'updated']) ? ['name' => fake()->word(), 'status' => 'active'] : null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'request_id' => fake()->uuid(),
            'metadata' => [
                'source' => fake()->randomElement(['web', 'mobile', 'api']),
                'duration_ms' => fake()->numberBetween(10, 5000),
            ],
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
