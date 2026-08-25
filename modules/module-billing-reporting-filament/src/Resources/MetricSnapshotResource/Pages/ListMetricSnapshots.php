<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Filament\Resources\MetricSnapshotResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Reporting\Filament\Resources\MetricSnapshotResource;

final class ListMetricSnapshots extends ListRecords
{
    protected static string $resource = MetricSnapshotResource::class;
}
