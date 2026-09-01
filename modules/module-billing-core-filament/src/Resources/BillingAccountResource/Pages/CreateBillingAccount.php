<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingAccountResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Liberu\Billing\Core\Filament\Resources\BillingAccountResource;

final class CreateBillingAccount extends CreateRecord
{
    protected static string $resource = BillingAccountResource::class;
}
