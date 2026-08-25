<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources\InvoiceScheduleResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceScheduleResource;

final class ListInvoiceSchedules extends ListRecords
{
    protected static string $resource = InvoiceScheduleResource::class;
}
