<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingContactResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingContactResource;
use Liberu\Billing\Core\Filament\Resources\Pages\CreateBillingCoreRecord;

final class CreateBillingContact extends CreateBillingCoreRecord
{
    protected static string $resource = BillingContactResource::class;
}
