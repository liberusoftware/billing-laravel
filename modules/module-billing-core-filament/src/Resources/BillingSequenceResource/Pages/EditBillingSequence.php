<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Filament\Resources\BillingSequenceResource\Pages;

use Liberu\Billing\Core\Filament\Resources\BillingSequenceResource;
use Liberu\Billing\Core\Filament\Resources\Pages\EditBillingCoreRecord;

final class EditBillingSequence extends EditBillingCoreRecord
{
    protected static string $resource = BillingSequenceResource::class;
}
