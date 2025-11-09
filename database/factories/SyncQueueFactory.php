<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SyncQueue>
 */
class SyncQueueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement(['create', 'update', 'delete']);
        $entityType = fake()->randomElement(['Product', 'Sale', 'Customer', 'Payment', 'StockMovement']);
        $status = fake()->randomElement(['pending', 'processing', 'completed', 'failed', 'conflict']);

        return [
            'shop_id' => null,
            'user_id' => null,
            'entity_type' => $entityType,
            'entity_id' => fake()->uuid(),
            'action' => $action,
            'data' => [
                'id' => fake()->uuid(),
                'name' => fake()->words(3, true),
                'updated_at' => now()->toIso8601String(),
            ],
            'client_timestamp' => fake()->dateTimeBetween('-1 hour', 'now'),
            'device_id' => fake()->uuid(),
            'status' => $status,
            'attempts' => fake()->numberBetween(0, 5),
            'last_attempt_at' => fake()->optional()->dateTimeBetween('-1 hour', 'now'),
            'error_message' => $status === 'failed' ? fake()->sentence() : null,
            'priority' => fake()->numberBetween(1, 10),
            'conflict_data' => $status === 'conflict' ? ['server_version' => fake()->numberBetween(1, 10)] : null,
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution' => null,
            'processed_at' => $status === 'completed' ? fake()->dateTimeBetween('-1 hour', 'now') : null,
        ];
    }

    /**
     * Indicate that the sync is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'attempts' => 0,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the sync has a conflict.
     */
    public function conflict(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'conflict',
            'conflict_data' => [
                'server_version' => fake()->numberBetween(2, 10),
                'client_version' => 1,
                'conflicting_fields' => ['name', 'price'],
            ],
        ]);
    }

    /**
     * Indicate that the sync is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }
}
