<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingCurrencyResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingCurrencyResource;
use Liberu\Billing\Core\Filament\Resources\Pages\CreateBillingCoreRecord;

final class CreateBillingCurrency extends CreateBillingCoreRecord
{
    protected static string $resource = BillingCurrencyResource::class;
}
