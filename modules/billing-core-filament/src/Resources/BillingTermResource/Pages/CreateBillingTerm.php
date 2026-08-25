<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingTermResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingTermResource;
use Liberu\Billing\Core\Filament\Resources\Pages\CreateBillingCoreRecord;

final class CreateBillingTerm extends CreateBillingCoreRecord
{
    protected static string $resource = BillingTermResource::class;
}
