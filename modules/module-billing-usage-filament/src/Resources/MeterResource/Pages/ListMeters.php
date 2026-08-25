<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Filament\Resources\MeterResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Usage\Filament\Resources\MeterResource;

final class ListMeters extends ListRecords
{
    protected static string $resource = MeterResource::class;
}
