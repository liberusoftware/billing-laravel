<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
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
        $team = Team::factory()->create();
        $project = Project::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        // 3 entries of 3600s on this project's task = 10800s.
        TimeEntry::factory()->count(3)->create(['task_id' => $task->id]);

        // Unrelated project/time (same team) should aggregate separately.
        $other = Project::factory()->create(['team_id' => $team->id]);
        $otherTask = Task::factory()->create(['project_id' => $other->id]);
        TimeEntry::factory()->create(['task_id' => $otherTask->id]);

        $perProject = (new ProjectReportService())->timeWorkedPerProject($team->id);

        $this->assertSame(10800, $perProject[$project->id]);
        $this->assertSame(3600, $perProject[$other->id]);
    }

    public function test_report_counts_projects_by_status(): void
    {
        $team = Team::factory()->create();
        Project::factory()->count(2)->create(['team_id' => $team->id, 'status' => ProjectStatus::Open]);
        Project::factory()->create(['team_id' => $team->id, 'status' => ProjectStatus::Completed]);

        $counts = (new ProjectReportService())->projectCountByStatus($team->id);

        $this->assertSame(2, $counts[ProjectStatus::Open->value]);
        $this->assertSame(1, $counts[ProjectStatus::Completed->value]);
    }

    public function test_report_splits_billable_and_non_billable_time(): void
    {
        $team = Team::factory()->create();
        $project = Project::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);
        TimeEntry::factory()->count(2)->create(['task_id' => $task->id, 'is_billable' => true]);
        TimeEntry::factory()->create(['task_id' => $task->id, 'is_billable' => false]);

        $split = (new ProjectReportService())->billableSplit($team->id);

        $this->assertSame(7200, $split['billable']);
        $this->assertSame(3600, $split['non_billable']);
    }

    public function test_report_aggregates_time_worked_per_staff(): void
    {
        $team = Team::factory()->create();
        $project = Project::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);
        $user = User::factory()->create();
        TimeEntry::factory()->count(2)->create(['task_id' => $task->id, 'user_id' => $user->id]);

        $perStaff = (new ProjectReportService())->timeWorkedPerStaff($team->id);

        $this->assertSame(7200, $perStaff[$user->id]);
    }

    public function test_report_is_scoped_to_current_team(): void
    {
        // Team A: one project, one billable entry of 3600s by user A.
        $teamA = Team::factory()->create();
        $userA = User::factory()->create();
        $projectA = Project::factory()->create(['team_id' => $teamA->id, 'status' => ProjectStatus::Open]);
        $taskA = Task::factory()->create(['project_id' => $projectA->id]);
        TimeEntry::factory()->create([
            'task_id' => $taskA->id,
            'user_id' => $userA->id,
            'duration_seconds' => 3600,
            'is_billable' => true,
        ]);

        // Team B: one project, one billable entry of 7200s by user B.
        $teamB = Team::factory()->create();
        $userB = User::factory()->create();
        $projectB = Project::factory()->create(['team_id' => $teamB->id, 'status' => ProjectStatus::Open]);
        $taskB = Task::factory()->create(['project_id' => $projectB->id]);
        TimeEntry::factory()->create([
            'task_id' => $taskB->id,
            'user_id' => $userB->id,
            'duration_seconds' => 7200,
            'is_billable' => true,
        ]);

        $report = new ProjectReportService();

        $perProject = $report->timeWorkedPerProject($teamA->id);
        $this->assertSame(3600, $perProject[$projectA->id] ?? null);
        $this->assertArrayNotHasKey($projectB->id, $perProject);

        $perStaff = $report->timeWorkedPerStaff($teamA->id);
        $this->assertSame(3600, $perStaff[$userA->id] ?? null);
        $this->assertArrayNotHasKey($userB->id, $perStaff);

        $this->assertSame(1, $report->projectCountByStatus($teamA->id)[ProjectStatus::Open->value] ?? null);

        // Only team A's billable seconds; team B's 7200s must not bleed in.
        $this->assertSame(3600, $report->billableSplit($teamA->id)['billable']);
    }
}
