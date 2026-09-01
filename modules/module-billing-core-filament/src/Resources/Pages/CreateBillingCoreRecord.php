<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Core\Actions\CreateBillingRecord;

abstract class CreateBillingCoreRecord extends CreateRecord
{
    protected function handleRecordCreation(array $data): Model
    {
        $teamId = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateBillingRecord::class)->execute(static::getResource()::getModel(), $data + ['team_id' => (int) $teamId]);
    }
}
