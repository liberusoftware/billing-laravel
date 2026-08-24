<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Liberu\Billing\Collections\Actions\OpenCollectionCase;
use Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource;

final class CreateCollectionCase extends CreateRecord
{
    protected static string $resource = CollectionCaseResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['team_id'] = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(OpenCollectionCase::class)->execute($data);
    }
}
