<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Filament\Resources\HostingCapabilityResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Hosting\Actions\CreateHostingCapability as CreateHostingCapabilityAction;
use Liberu\Billing\Hosting\Filament\Resources\HostingCapabilityResource;

final class CreateHostingCapability extends CreateRecord
{
    protected static string $resource = HostingCapabilityResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateHostingCapabilityAction::class)->handle((int) $teamId, $data);
    }
}
