<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources\InvoiceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceResource;

final class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
}
