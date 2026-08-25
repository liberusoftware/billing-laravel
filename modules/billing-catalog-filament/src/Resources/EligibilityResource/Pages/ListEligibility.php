<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources\EligibilityResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Catalog\Filament\Resources\EligibilityResource;

final class ListEligibility extends ListRecords
{
    protected static string $resource = EligibilityResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
