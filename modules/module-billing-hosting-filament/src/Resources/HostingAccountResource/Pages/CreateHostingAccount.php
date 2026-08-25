<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Filament\Resources\HostingAccountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Hosting\Actions\CreateHostingAccount as CreateHostingAccountAction;
use Liberu\Billing\Hosting\Filament\Resources\HostingAccountResource;

final class CreateHostingAccount extends CreateRecord
{
    protected static string $resource = HostingAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateHostingAccountAction::class)->handle((int) $teamId, $data);
    }
}
