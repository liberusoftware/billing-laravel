<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TicketDepartment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketDepartment>
 */
class TicketDepartmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Support', 'Billing', 'Sales', 'Abuse']),
            'email' => fake()->unique()->companyEmail(),
            'is_active' => true,
        ];
    }
}
