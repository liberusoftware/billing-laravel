<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TicketEscalationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketEscalationRule>
 */
class TicketEscalationRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => null,
            'minutes_without_response' => 60,
            'action' => fake()->randomElement(['raise_priority', 'reassign', 'notify']),
            'target_user_id' => null,
            'is_active' => true,
        ];
    }
}
