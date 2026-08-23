<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Products_Service;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'product_service_id' => Products_Service::factory(),
            'start_date' => fake()->date(),
            'end_date' => fake()->optional()->date(),
            'renewal_period' => fake()->randomElement(['monthly', 'quarterly', 'semi-annually', 'annually']),
            'status' => fake()->randomElement(['active', 'suspended', 'cancelled', 'expired']),
            'price' => fake()->randomFloat(2, 1, 1000),
        ];
    }
}
