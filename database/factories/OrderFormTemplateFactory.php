<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderFormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderFormTemplate>
 */
class OrderFormTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'is_active' => true,
            'config' => ['plan_ids' => []],
        ];
    }

    /**
     * @param  array<int, int>  $planIds
     */
    public function offering(array $planIds): static
    {
        return $this->state(fn (): array => ['config' => ['plan_ids' => $planIds]]);
    }
}
