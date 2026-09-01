<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingSettingResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingSettingResource;
use Liberu\Billing\Core\Filament\Resources\Pages\CreateBillingCoreRecord;

final class CreateBillingSetting extends CreateBillingCoreRecord
{
    protected static string $resource = BillingSettingResource::class;
}
