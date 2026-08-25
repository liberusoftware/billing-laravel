<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament\Resources\IspCapabilityResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Isp\Actions\CreateIspCapability as CreateIspCapabilityAction;
use Liberu\Billing\Isp\Filament\Resources\IspCapabilityResource;

final class CreateIspCapability extends CreateRecord
{
    protected static string $resource = IspCapabilityResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateIspCapabilityAction::class)->handle((int) $teamId, $data);
    }
}
