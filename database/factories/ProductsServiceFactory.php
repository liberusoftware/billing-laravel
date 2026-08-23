<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Products_Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Products_Service>
 */
class ProductsServiceFactory extends Factory
{
    #[\Override]
    protected $model = Products_Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word,
            'description' => fake()->paragraph,
            'base_price' => fake()->randomFloat(2, 1, 1000),
            'type' => fake()->randomElement(['product', 'service', 'hosting']),
        ];
    }
}
