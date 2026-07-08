<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\TimeEntry;

class ProjectReportService
{
    /**
     * Project count keyed by status value (e.g. ['open' => 3, 'completed' => 1]).
     *
     * @return array<string, int>
     */
    public function projectCountByStatus(): array
    {
        return Project::query()
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
    public function timeWorkedPerProject(): array
    {
        return TimeEntry::query()
            ->join('tasks', 'time_entries.task_id', '=', 'tasks.id')
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
    public function timeWorkedPerStaff(): array
    {
        return TimeEntry::query()
            ->selectRaw('user_id, SUM(duration_seconds) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id')
            ->map(fn ($seconds): int => (int) $seconds)
            ->all();
    }

    /**
     * Billable vs non-billable seconds.
     *
     * @return array{billable: int, non_billable: int}
     */
    public function billableSplit(): array
    {
        return [
            'billable' => (int) TimeEntry::query()->where('is_billable', true)->sum('duration_seconds'),
            'non_billable' => (int) TimeEntry::query()->where('is_billable', false)->sum('duration_seconds'),
        ];
    }
}
