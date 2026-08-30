<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources\PricingContractResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Pricing\Filament\Resources\PricingContractResource;

final class ListPricingContracts extends ListRecords
{
    protected static string $resource = PricingContractResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
