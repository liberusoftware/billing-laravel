<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\PlanResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Catalog\Filament\Resources\PlanResource;

final class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
