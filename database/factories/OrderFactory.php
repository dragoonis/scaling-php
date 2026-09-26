<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference' => fake()->unique()->bothify('ORD-########'),
            'total' => fake()->numberBetween(500, 250000),
            'status' => fake()->randomElement(['pending', 'paid', 'shipped', 'delivered']),
        ];
    }
}
