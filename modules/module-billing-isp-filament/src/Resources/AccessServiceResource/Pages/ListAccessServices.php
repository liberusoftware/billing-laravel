<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament\Resources\AccessServiceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Isp\Filament\Resources\AccessServiceResource;

final class ListAccessServices extends ListRecords
{
    protected static string $resource = AccessServiceResource::class;
}
