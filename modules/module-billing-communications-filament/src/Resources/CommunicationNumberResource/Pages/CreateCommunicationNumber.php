<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\CommunicationNumberResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Communications\Actions\ProvisionCommunicationNumber;
use Liberu\Billing\Communications\Filament\Resources\CommunicationNumberResource;

final class CreateCommunicationNumber extends CreateRecord
{
    protected static string $resource = CommunicationNumberResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ProvisionCommunicationNumber::class)->handle($this->team(), $data);
    }

    private function team(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
