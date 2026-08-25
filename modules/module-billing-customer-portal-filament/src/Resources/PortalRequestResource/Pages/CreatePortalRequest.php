<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament\Resources\PortalRequestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\CustomerPortal\Actions\CreatePortalRequest as CreatePortalRequestAction;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalRequestResource;

final class CreatePortalRequest extends CreateRecord
{
    protected static string $resource = PortalRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreatePortalRequestAction::class)->handle((int) $team, $data);
    }
}
