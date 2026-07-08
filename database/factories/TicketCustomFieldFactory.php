<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TicketCustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketCustomField>
 */
class TicketCustomFieldFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->randomElement(['Server hostname', 'cPanel username', 'Domain']),
            'type' => 'text',
            'options' => null,
            'is_required' => false,
            'is_active' => true,
        ];
    }
}
