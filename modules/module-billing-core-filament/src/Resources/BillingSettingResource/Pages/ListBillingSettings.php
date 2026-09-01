<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingSettingResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingSettingResource;
use Liberu\Billing\Core\Filament\Resources\Pages\ListBillingCoreRecords;

final class ListBillingSettings extends ListBillingCoreRecords
{
    protected static string $resource = BillingSettingResource::class;
}
