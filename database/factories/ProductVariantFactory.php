<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Reguler',
            'price' => fake()->numberBetween(10, 500) * 1000,
            'is_default' => true,
            'is_active' => true,
            'is_available' => true,
        ];
    }

    /**
     * Indicate that the variant cannot be selected for new orders.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'is_default' => false,
        ]);
    }

    /**
     * Indicate that the variant is sold out.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
