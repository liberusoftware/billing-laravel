<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament\Resources\PortalItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\CustomerPortal\Actions\CreatePortalItem as CreatePortalItemAction;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalItemResource;

final class CreatePortalItem extends CreateRecord
{
    protected static string $resource = PortalItemResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $team = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreatePortalItemAction::class)->handle((int) $team, $data);
    }
}
