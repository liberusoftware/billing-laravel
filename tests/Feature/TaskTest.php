<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_marking_task_complete_sets_completed_at(): void
    {
        $task = Task::factory()->create(['is_complete' => false, 'completed_at' => null]);

        $task->markComplete();

        $this->assertTrue($task->fresh()->is_complete);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_outstanding_scope_excludes_completed_tasks(): void
    {
        $open = Task::factory()->create(['is_complete' => false]);
        $done = Task::factory()->create(['is_complete' => true, 'completed_at' => now()]);

        $outstanding = Task::outstanding()->pluck('id');

        $this->assertTrue($outstanding->contains($open->id));
        $this->assertFalse($outstanding->contains($done->id));
    }
}
