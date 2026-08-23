<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $started = fake()->dateTimeBetween('-1 week', 'now');
        $ended = (clone $started)->modify('+1 hour');

        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'started_at' => $started,
            'ended_at' => $ended,
            'duration_seconds' => 3600,
            'is_billable' => true,
            'rate' => fake()->randomFloat(2, 20, 150),
        ];
    }

    /**
     * A running (not-yet-stopped) timer.
     */
    public function running(): static
    {
        return $this->state(fn (): array => [
            'started_at' => now(),
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);
    }
}
