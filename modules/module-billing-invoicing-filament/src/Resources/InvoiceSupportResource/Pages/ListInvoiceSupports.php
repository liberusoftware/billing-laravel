<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources\InvoiceSupportResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceSupportResource;

final class ListInvoiceSupports extends ListRecords
{
    protected static string $resource = InvoiceSupportResource::class;
}
