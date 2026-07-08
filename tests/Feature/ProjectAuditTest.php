<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_project_records_activity_entry(): void
    {
        $project = Project::factory()->create();

        $project->update(['name' => 'Renamed project']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Project::class,
            'auditable_id' => $project->getKey(),
            'event' => 'project_updated',
        ]);
    }

    public function test_creating_project_records_activity_entry(): void
    {
        $project = Project::factory()->create();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Project::class,
            'auditable_id' => $project->getKey(),
            'event' => 'project_created',
        ]);
    }

    public function test_deleting_project_records_activity_entry(): void
    {
        $project = Project::factory()->create();

        $project->delete();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Project::class,
            'auditable_id' => $project->getKey(),
            'event' => 'project_deleted',
        ]);
    }

    public function test_task_changes_record_activity_entries(): void
    {
        $task = Task::factory()->create();
        $task->update(['title' => 'Renamed task']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Task::class,
            'auditable_id' => $task->getKey(),
            'event' => 'task_updated',
        ]);
    }

    public function test_time_entry_changes_record_activity_entries(): void
    {
        $timeEntry = TimeEntry::factory()->create();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => TimeEntry::class,
            'auditable_id' => $timeEntry->getKey(),
            'event' => 'time_entry_created',
        ]);
    }
}
