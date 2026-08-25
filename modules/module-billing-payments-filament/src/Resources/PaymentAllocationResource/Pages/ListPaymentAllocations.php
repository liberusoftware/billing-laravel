<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources\PaymentAllocationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Payments\Filament\Resources\PaymentAllocationResource;

final class ListPaymentAllocations extends ListRecords
{
    protected static string $resource = PaymentAllocationResource::class;
}
