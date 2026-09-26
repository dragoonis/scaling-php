<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-########'),
            'description' => fake()->sentence(12),
            'price' => fake()->numberBetween(100, 99900),
            'stock' => fake()->numberBetween(0, 500),
        ];
    }
}
