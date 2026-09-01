<?php

namespace Liberu\Billing\Core\Filament\Resources\BillingTaxExemptionResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingTaxExemptionResource;
use Liberu\Billing\Core\Filament\Resources\Pages\ListBillingCoreRecords;

final class ListBillingTaxExemptions extends ListBillingCoreRecords
{
    protected static string $resource = BillingTaxExemptionResource::class;
}
