<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources\DomainResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Domains\Actions\CreateDomain as CreateDomainAction;
use Liberu\Billing\Domains\Filament\Resources\DomainResource;

final class CreateDomain extends CreateRecord
{
    protected static string $resource = DomainResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateDomainAction::class)->handle((int) $teamId, $data);
    }
}
