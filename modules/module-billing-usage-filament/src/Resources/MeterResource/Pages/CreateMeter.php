<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Filament\Resources\MeterResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Usage\Actions\DefineMeter;
use Liberu\Billing\Usage\Filament\Resources\MeterResource;

final class CreateMeter extends CreateRecord
{
    protected static string $resource = MeterResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return app(DefineMeter::class)->execute($data);
    }
}
