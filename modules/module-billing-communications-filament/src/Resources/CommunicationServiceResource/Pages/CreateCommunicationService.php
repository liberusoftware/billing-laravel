<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\CommunicationServiceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Communications\Actions\CreateCommunicationService as CreateCommunicationServiceAction;
use Liberu\Billing\Communications\Filament\Resources\CommunicationServiceResource;

final class CreateCommunicationService extends CreateRecord
{
    protected static string $resource = CommunicationServiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateCommunicationServiceAction::class)->handle($this->team(), $data);
    }

    private function team(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
