<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Filament\Resources\MetricSnapshotResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Reporting\Actions\CreateMetricSnapshot as CreateMetricSnapshotAction;
use Liberu\Billing\Reporting\Filament\Resources\MetricSnapshotResource;

final class CreateMetricSnapshot extends CreateRecord
{
    protected static string $resource = MetricSnapshotResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateMetricSnapshotAction::class)->handle((int) $team, $data);
    }
}
