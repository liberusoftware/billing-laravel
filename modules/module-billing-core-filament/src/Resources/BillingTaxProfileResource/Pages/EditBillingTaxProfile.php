<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingTaxProfileResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingTaxProfileResource;
use Liberu\Billing\Core\Filament\Resources\Pages\EditBillingCoreRecord;

final class EditBillingTaxProfile extends EditBillingCoreRecord
{
    protected static string $resource = BillingTaxProfileResource::class;
}
