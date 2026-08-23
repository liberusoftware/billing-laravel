<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\InvoiceResource\Pages;

use App\Filament\Client\Resources\InvoiceResource;
use Filament\Resources\Pages\ViewRecord;
use Override;

class ViewInvoice extends ViewRecord
{
    #[Override]
    protected static string $resource = InvoiceResource::class;
}
