<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources\VoipAccountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Communications\Filament\Resources\VoipAccountResource;

final class CreateVoipAccount extends CreateRecord
{
    protected static string $resource = VoipAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = (int) (auth()->user()->current_team_id ?? auth()->user()->currentTeam->id);

        return $data;
    }
}
