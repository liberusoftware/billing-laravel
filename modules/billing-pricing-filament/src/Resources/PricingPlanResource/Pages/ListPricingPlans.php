<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources\PricingPlanResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Pricing\Filament\Resources\PricingPlanResource;

final class ListPricingPlans extends ListRecords
{
    protected static string $resource = PricingPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
