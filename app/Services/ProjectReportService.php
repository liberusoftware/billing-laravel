<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Team;
use App\Models\TimeEntry;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class ProjectReportService
{
    /**
     * Project count keyed by status value (e.g. ['open' => 3, 'completed' => 1]).
     *
     * @return array<string, int>
     */
    public function projectCountByStatus(?int $teamId = null): array
    {
        return Project::query()
            ->where('team_id', $this->resolveTeamId($teamId))
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * Total seconds worked, keyed by project id.
     *
     * @return array<int, int>
     */
    public function timeWorkedPerProject(?int $teamId = null): array
    {
        return $this->teamTimeEntries($this->resolveTeamId($teamId))
            ->selectRaw('tasks.project_id, SUM(duration_seconds) as aggregate')
            ->groupBy('tasks.project_id')
            ->pluck('aggregate', 'tasks.project_id')
            ->map(fn ($seconds): int => (int) $seconds)
            ->all();
    }

    /**
     * Total seconds worked, keyed by staff user id.
     *
     * @return array<int, int>
     */
    public function timeWorkedPerStaff(?int $teamId = null): array
    {
        return $this->teamTimeEntries($this->resolveTeamId($teamId))
            ->selectRaw('time_entries.user_id, SUM(duration_seconds) as aggregate')
            ->groupBy('time_entries.user_id')
            ->pluck('aggregate', 'time_entries.user_id')
            ->map(fn ($seconds): int => (int) $seconds)
            ->all();
    }

    /**
     * Billable vs non-billable seconds.
     *
     * @return array{billable: int, non_billable: int}
     */
    public function billableSplit(?int $teamId = null): array
    {
        $teamId = $this->resolveTeamId($teamId);

        return [
            'billable' => (int) $this->teamTimeEntries($teamId)->where('is_billable', true)->sum('duration_seconds'),
            'non_billable' => (int) $this->teamTimeEntries($teamId)->where('is_billable', false)->sum('duration_seconds'),
        ];
    }

    /**
     * Time entries joined through to their project, scoped to the given team.
     *
     * @return Builder<TimeEntry>
     */
    private function teamTimeEntries(int $teamId): Builder
    {
        return TimeEntry::query()
            ->join('tasks', 'time_entries.task_id', '=', 'tasks.id')
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('projects.team_id', $teamId);
    }

    /**
     * Resolve the team to scope to, defaulting to the active panel tenant.
     *
     * ponytail: this report is only ever rendered inside the Admin (Team-tenant)
     * panel, so falling back to the current tenant keeps sibling callers (e.g.
     * ProjectStatsWidget) team-scoped without threading the id through each one.
     * A missing tenant yields team_id 0, which matches nothing (fail closed).
     */
    private function resolveTeamId(?int $teamId): int
    {
        if ($teamId !== null) {
            return $teamId;
        }

        $tenant = Filament::getTenant();

        return $tenant instanceof Team ? $tenant->id : 0;
    }
}
