<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Filament\Resources\ProvisionedServiceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Provisioning\Actions\CreateProvisionedService as CreateProvisionedServiceAction;
use Liberu\Billing\Provisioning\Filament\Resources\ProvisionedServiceResource;

final class CreateProvisionedService extends CreateRecord
{
    protected static string $resource = ProvisionedServiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');
        $data['team_id'] = $teamId === null ? null : (int) $teamId;

        return app(CreateProvisionedServiceAction::class)->execute($data);
    }
}
