<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\BundleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Catalog\Actions\CreateCatalogRecord;
use Liberu\Billing\Catalog\Filament\Resources\BundleResource;
use Liberu\Billing\Catalog\Models\Bundle;

final class CreateBundle extends CreateRecord
{
    protected static string $resource = BundleResource::class;

    protected function handleRecordCreation(array $data): Bundle
    {
        $data['team_id'] = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        $record = app(CreateCatalogRecord::class)->execute(Bundle::class, $data);

        if (! $record instanceof Bundle) {
            throw new \LogicException('Catalog action returned an invalid record type.');
        }

        return $record;
    }
}
