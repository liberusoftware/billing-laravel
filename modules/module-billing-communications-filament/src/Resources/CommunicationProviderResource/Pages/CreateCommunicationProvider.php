<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\CommunicationProviderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Communications\Actions\CreateCommunicationProvider as CreateCommunicationProviderAction;
use Liberu\Billing\Communications\Filament\Resources\CommunicationProviderResource;

final class CreateCommunicationProvider extends CreateRecord
{
    protected static string $resource = CommunicationProviderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateCommunicationProviderAction::class)->handle($this->team(), $data);
    }

    private function team(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
