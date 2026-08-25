<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Filament\Resources\UsageRecordResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Usage\Filament\Resources\UsageRecordResource;

final class ListUsageRecords extends ListRecords
{
    protected static string $resource = UsageRecordResource::class;
}
