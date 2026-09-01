<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingSequenceResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingSequenceResource;
use Liberu\Billing\Core\Filament\Resources\Pages\ListBillingCoreRecords;

final class ListBillingSequences extends ListBillingCoreRecords
{
    protected static string $resource = BillingSequenceResource::class;
}
