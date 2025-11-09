<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Electronics',
            'Spare Parts',
            'Hardware',
            'Clothing',
            'Groceries',
            'Pharmaceuticals',
            'Beverages',
            'Home Appliances',
            'Office Supplies',
            'Cosmetics',
        ]);

        return [
            'shop_id' => null,
            'name' => $name,
            'name_chichewa' => fake()->optional()->word(),
            'slug' => Str::slug($name).'-'.fake()->numberBetween(1, 999),
            'description' => fake()->optional()->sentence(),
            'parent_id' => null,
            'path' => null,
            'depth' => 0,
            'display_order' => fake()->numberBetween(1, 100),
            'icon' => fake()->optional()->randomElement(['📱', '🔧', '🛠️', '👕', '🍞', '💊', '🍺', '🏠', '📄', '💄']),
            'color' => fake()->optional()->hexColor(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the category is a subcategory.
     */
    public function subcategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'depth' => 1,
        ]);
    }
}
