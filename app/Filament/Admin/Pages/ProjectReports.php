<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\ProjectStatsWidget;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Services\ProjectReportService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Override;
use UnitEnum;

class ProjectReports extends Page
{
    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Projects';

    protected static ?string $title = 'Project Reports';

    #[Override]
    protected string $view = 'filament.admin.pages.project-reports';

    /** @var array<int, array{name: string, hours: float}> */
    public array $perProject = [];

    /** @var array<int, array{name: string, hours: float}> */
    public array $perStaff = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        $teamId = $tenant instanceof Team ? $tenant->id : 0;
        $report = new ProjectReportService();

        $byProject = $report->timeWorkedPerProject($teamId);
        $projectNames = Project::where('team_id', $teamId)
            ->whereIn('id', array_keys($byProject))
            ->pluck('name', 'id');
        $this->perProject = collect($byProject)
            ->map(fn (int $seconds, int $id): array => [
                'name' => $projectNames[$id] ?? "#{$id}",
                'hours' => round($seconds / 3600, 1),
            ])
            ->values()
            ->all();

        $byStaff = $report->timeWorkedPerStaff($teamId);
        // $byStaff ids already come from this team's time entries, so this lookup can't leak.
        $staffNames = User::whereIn('id', array_keys($byStaff))->pluck('name', 'id');
        $this->perStaff = collect($byStaff)
            ->map(fn (int $seconds, int $id): array => [
                'name' => $staffNames[$id] ?? "#{$id}",
                'hours' => round($seconds / 3600, 1),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, class-string>
     */
    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            ProjectStatsWidget::class,
        ];
    }
}
