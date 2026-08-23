<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingAccountResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Core\Filament\Resources\BillingAccountResource;

final class ListBillingAccounts extends ListRecords
{
    protected static string $resource = BillingAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
