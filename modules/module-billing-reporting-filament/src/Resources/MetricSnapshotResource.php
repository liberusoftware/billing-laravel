<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Reporting\Filament\Resources\MetricSnapshotResource\Pages\CreateMetricSnapshot;
use Liberu\Billing\Reporting\Filament\Resources\MetricSnapshotResource\Pages\ListMetricSnapshots;
use Liberu\Billing\Reporting\Models\MetricSnapshot;

final class MetricSnapshotResource extends Resource
{
    protected static ?string $model = MetricSnapshot::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('status')->default('ready')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMetricSnapshots::route('/'), 'create' => CreateMetricSnapshot::route('/create')];
    }
}
