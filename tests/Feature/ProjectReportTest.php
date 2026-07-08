<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\ProjectReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_aggregates_time_worked_per_project(): void
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        // 3 entries of 3600s on this project's task = 10800s.
        TimeEntry::factory()->count(3)->create(['task_id' => $task->id]);

        // Unrelated project/time should not bleed in.
        $other = Project::factory()->create();
        $otherTask = Task::factory()->create(['project_id' => $other->id]);
        TimeEntry::factory()->create(['task_id' => $otherTask->id]);

        $perProject = (new ProjectReportService)->timeWorkedPerProject();

        $this->assertSame(10800, $perProject[$project->id]);
        $this->assertSame(3600, $perProject[$other->id]);
    }

    public function test_report_counts_projects_by_status(): void
    {
        Project::factory()->count(2)->create(['status' => ProjectStatus::Open]);
        Project::factory()->create(['status' => ProjectStatus::Completed]);

        $counts = (new ProjectReportService)->projectCountByStatus();

        $this->assertSame(2, $counts[ProjectStatus::Open->value]);
        $this->assertSame(1, $counts[ProjectStatus::Completed->value]);
    }

    public function test_report_splits_billable_and_non_billable_time(): void
    {
        $task = Task::factory()->create();
        TimeEntry::factory()->count(2)->create(['task_id' => $task->id, 'is_billable' => true]);
        TimeEntry::factory()->create(['task_id' => $task->id, 'is_billable' => false]);

        $split = (new ProjectReportService)->billableSplit();

        $this->assertSame(7200, $split['billable']);
        $this->assertSame(3600, $split['non_billable']);
    }

    public function test_report_aggregates_time_worked_per_staff(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        TimeEntry::factory()->count(2)->create(['task_id' => $task->id, 'user_id' => $user->id]);

        $perStaff = (new ProjectReportService)->timeWorkedPerStaff();

        $this->assertSame(7200, $perStaff[$user->id]);
    }
}
