<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingAccountResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Liberu\Billing\Core\Filament\Resources\BillingAccountResource;

final class EditBillingAccount extends EditRecord
{
    protected static string $resource = BillingAccountResource::class;
}
