<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('INV-####??')),
            'nombre' => ucfirst(fake()->words(2, true)),
            'precio' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(10, 100),
            'stock_minimo' => 5,
        ];
    }

    public function conStock(int $stock, int $minimo = 5): static
    {
        return $this->state(fn () => ['stock' => $stock, 'stock_minimo' => $minimo]);
    }
}
