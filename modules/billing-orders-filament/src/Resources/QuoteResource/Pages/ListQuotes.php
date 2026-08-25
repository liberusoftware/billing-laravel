<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources\QuoteResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Orders\Filament\Resources\QuoteResource;

final class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;
}
