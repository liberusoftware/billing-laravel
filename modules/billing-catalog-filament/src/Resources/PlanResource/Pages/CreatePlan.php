<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\PlanResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Catalog\Actions\CreateCatalogRecord;
use Liberu\Billing\Catalog\Filament\Resources\PlanResource;
use Liberu\Billing\Catalog\Models\Plan;

final class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function handleRecordCreation(array $data): Plan
    {
        $data['team_id'] = data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id');

        return app(CreateCatalogRecord::class)->execute(Plan::class, $data);
    }
}
