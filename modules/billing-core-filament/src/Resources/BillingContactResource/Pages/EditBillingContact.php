<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingContactResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingContactResource;
use Liberu\Billing\Core\Filament\Resources\Pages\EditBillingCoreRecord;

final class EditBillingContact extends EditBillingCoreRecord
{
    protected static string $resource = BillingContactResource::class;
}
