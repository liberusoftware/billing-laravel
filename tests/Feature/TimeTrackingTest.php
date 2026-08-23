<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\TimeTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_stopping_timer_records_duration(): void
    {
        $entry = TimeEntry::factory()->running()->create(['started_at' => now()->subMinutes(30)]);

        app(TimeTrackingService::class)->stop($entry);

        $fresh = $entry->fresh();
        $this->assertNotNull($fresh->ended_at);
        $this->assertEqualsWithDelta(1800, $fresh->duration_seconds, 5);
    }

    public function test_user_cannot_have_two_running_timers(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $service = app(TimeTrackingService::class);

        $service->start($task, $user);
        $service->start($task, $user);

        $running = TimeEntry::running()->where('user_id', $user->id)->count();
        $this->assertSame(1, $running);
    }
}
