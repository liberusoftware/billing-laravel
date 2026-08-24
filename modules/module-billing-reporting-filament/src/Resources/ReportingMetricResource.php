<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Reporting\Models\ReportingMetric;

final class ReportingMetricResource extends Resource
{
    protected static ?string $model = ReportingMetric::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('metric')->badge(), TextColumn::make('period_start')->date(), TextColumn::make('period_end')->date(), TextColumn::make('value'), TextColumn::make('currency')]);
    }
}
