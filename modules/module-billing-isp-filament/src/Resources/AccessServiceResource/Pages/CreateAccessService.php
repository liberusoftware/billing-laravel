<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament\Resources\AccessServiceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Isp\Actions\CreateAccessService as CreateAccessServiceAction;
use Liberu\Billing\Isp\Filament\Resources\AccessServiceResource;

final class CreateAccessService extends CreateRecord
{
    protected static string $resource = AccessServiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateAccessServiceAction::class)->handle((int) $teamId, $data);
    }
}
