<?php

namespace Liberu\Billing\Core\Filament\Resources\BillingTaxExemptionResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingTaxExemptionResource;
use Liberu\Billing\Core\Filament\Resources\Pages\CreateBillingCoreRecord;

final class CreateBillingTaxExemption extends CreateBillingCoreRecord
{
    protected static string $resource = BillingTaxExemptionResource::class;
}
