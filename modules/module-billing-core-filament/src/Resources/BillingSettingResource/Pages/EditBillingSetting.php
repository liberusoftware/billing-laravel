<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingSettingResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingSettingResource;
use Liberu\Billing\Core\Filament\Resources\Pages\EditBillingCoreRecord;

final class EditBillingSetting extends EditBillingCoreRecord
{
    protected static string $resource = BillingSettingResource::class;
}
