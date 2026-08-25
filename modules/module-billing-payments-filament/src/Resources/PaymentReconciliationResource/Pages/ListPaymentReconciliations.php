<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentReconciliationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Payments\Filament\Resources\PaymentReconciliationResource;

final class ListPaymentReconciliations extends ListRecords
{
    protected static string $resource = PaymentReconciliationResource::class;
}
