<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roleName = fake()->randomElement(['Cashier', 'Manager', 'Inventory Clerk', 'Accountant', 'Sales Person']);

        return [
            'shop_id' => null,
            'name' => strtolower(str_replace(' ', '_', $roleName)),
            'display_name' => $roleName,
            'description' => fake()->sentence(),
            'is_system_role' => false,
            'permissions' => $this->getPermissionsForRole($roleName),
        ];
    }

    /**
     * Get permissions based on role name.
     */
    protected function getPermissionsForRole(string $roleName): array
    {
        $permissions = [
            'Cashier' => ['view_products', 'create_sales', 'process_payments', 'view_customers'],
            'Manager' => ['view_products', 'create_sales', 'manage_products', 'manage_customers', 'view_reports', 'manage_users'],
            'Inventory Clerk' => ['view_products', 'manage_products', 'manage_suppliers', 'create_purchase_orders'],
            'Accountant' => ['view_reports', 'manage_credits', 'view_payments', 'export_data'],
            'Sales Person' => ['view_products', 'create_sales', 'manage_customers', 'view_customers'],
        ];

        return $permissions[$roleName] ?? ['view_products'];
    }

    /**
     * Indicate that the role is a system role.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system_role' => true,
        ]);
    }
}
