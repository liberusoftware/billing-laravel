<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\ConfigurableOptionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Catalog\Actions\CreateCatalogRecord;
use Liberu\Billing\Catalog\Filament\Resources\ConfigurableOptionResource;
use Liberu\Billing\Catalog\Models\ConfigurableOption;

final class CreateConfigurableOption extends CreateRecord
{
    protected static string $resource = ConfigurableOptionResource::class;

    protected function handleRecordCreation(array $data): ConfigurableOption
    {
        $data['team_id'] = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateCatalogRecord::class)->execute(ConfigurableOption::class, $data);
    }
}
