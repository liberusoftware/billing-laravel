<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDueDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_scope_returns_only_past_due_open_projects(): void
    {
        $overdue = Project::factory()->create([
            'due_date' => now()->subDays(3), 'status' => ProjectStatus::InProgress,
        ]);
        $future = Project::factory()->create([
            'due_date' => now()->addDays(3), 'status' => ProjectStatus::Open,
        ]);
        $pastButDone = Project::factory()->create([
            'due_date' => now()->subDays(3), 'status' => ProjectStatus::Completed,
        ]);

        $ids = Project::overdue()->pluck('id');

        $this->assertTrue($ids->contains($overdue->id));
        $this->assertFalse($ids->contains($future->id));
        $this->assertFalse($ids->contains($pastButDone->id));
    }

    public function test_due_within_scope_returns_only_upcoming_incomplete_tasks(): void
    {
        $soon = Task::factory()->create(['due_date' => now()->addDays(2), 'is_complete' => false]);
        $later = Task::factory()->create(['due_date' => now()->addDays(20), 'is_complete' => false]);
        $soonDone = Task::factory()->create(['due_date' => now()->addDays(2), 'is_complete' => true]);

        $ids = Task::dueWithin(7)->pluck('id');

        $this->assertTrue($ids->contains($soon->id));
        $this->assertFalse($ids->contains($later->id));
        $this->assertFalse($ids->contains($soonDone->id));
    }
}
