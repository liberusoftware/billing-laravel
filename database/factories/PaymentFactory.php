<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            // ponytail: no PaymentGatewayFactory exists; create one inline so factory-built payments satisfy the NOT NULL FK.
            'payment_gateway_id' => fn (): int => PaymentGateway::create([
                'name' => fake()->company(),
                'api_key' => fake()->uuid(),
                'secret_key' => fake()->uuid(),
            ])->id,
            'currency' => 'USD',
            'payment_date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 10, 10000),
            'payment_method' => fake()->randomElement(['credit card', 'bank transfer', 'PayPal']),
            'transaction_id' => fake()->unique()->uuid(),
        ];
    }
}
