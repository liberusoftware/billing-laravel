<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Filament\Resources\ReportingMetricResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Reporting\Actions\RecordReportingMetric;
use Liberu\Billing\Reporting\Filament\Resources\ReportingMetricResource;

final class CreateReportingMetric extends CreateRecord
{
    protected static string $resource = ReportingMetricResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(RecordReportingMetric::class)->execute((int) $team, $data);
    }
}
