<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\AddonResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Catalog\Actions\CreateCatalogRecord;
use Liberu\Billing\Catalog\Filament\Resources\AddonResource;
use Liberu\Billing\Catalog\Models\Addon;

final class CreateAddon extends CreateRecord
{
    protected static string $resource = AddonResource::class;

    protected function handleRecordCreation(array $data): Addon
    {
        $data['team_id'] = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateCatalogRecord::class)->execute(Addon::class, $data);
    }
}
