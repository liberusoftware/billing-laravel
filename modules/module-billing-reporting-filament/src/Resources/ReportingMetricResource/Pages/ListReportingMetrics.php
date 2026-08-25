<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Filament\Resources\ReportingMetricResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Reporting\Filament\Resources\ReportingMetricResource;

final class ListReportingMetrics extends ListRecords
{
    protected static string $resource = ReportingMetricResource::class;
}
