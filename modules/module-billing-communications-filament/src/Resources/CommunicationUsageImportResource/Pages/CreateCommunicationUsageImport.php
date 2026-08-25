<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\CommunicationUsageImportResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Communications\Actions\ImportCommunicationUsage;
use Liberu\Billing\Communications\Filament\Resources\CommunicationUsageImportResource;

final class CreateCommunicationUsageImport extends CreateRecord
{
    protected static string $resource = CommunicationUsageImportResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ImportCommunicationUsage::class)->handle($this->team(), $data);
    }

    private function team(): int
    {
        return (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
    }
}
