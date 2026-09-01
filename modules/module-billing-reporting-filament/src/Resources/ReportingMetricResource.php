<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Filament\Resources;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Reporting\Actions\CalculateReportingMetric;
use Liberu\Billing\Reporting\Actions\ExportReportingMetrics;
use Liberu\Billing\Reporting\Actions\RecordReportingMetric;
use Liberu\Billing\Reporting\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Reporting\Filament\Resources\ReportingMetricResource\Pages\CreateReportingMetric;
use Liberu\Billing\Reporting\Filament\Resources\ReportingMetricResource\Pages\ListReportingMetrics;
use Liberu\Billing\Reporting\Models\ReportingMetric;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportingMetricResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    use ScopesCurrentTeam;

    protected static ?string $model = ReportingMetric::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('metric')->required()->maxLength(32), TextInput::make('period_start')->type('date')->required(),
            TextInput::make('period_end')->type('date')->required(), TextInput::make('value')->numeric()->required(),
            TextInput::make('currency')->length(3), TextInput::make('source')->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('metric')->badge(), TextColumn::make('period_start')->date()->sortable(), TextColumn::make('period_end')->date()->sortable(), TextColumn::make('value'), TextColumn::make('currency'), TextColumn::make('source')])->actions([
            Action::make('recalculate')->label('Recalculate')->form([
                TextInput::make('currency')->default('USD')->length(3),
            ])->action(function (ReportingMetric $record, array $data): void {
                $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
                $calculated = app(CalculateReportingMetric::class)->execute($team, (string) $record->getRawOriginal('metric'), CarbonImmutable::parse((string) $record->getRawOriginal('period_start')), CarbonImmutable::parse((string) $record->getRawOriginal('period_end')), $data['currency'] ?? (string) $record->getRawOriginal('currency'));
                app(RecordReportingMetric::class)->execute($team, $calculated);
            }),
        ])->headerActions([
            Action::make('exportCsv')->label('Export CSV')->action(function (): StreamedResponse {
                $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
                $csv = app(ExportReportingMetrics::class)->execute($team);

                return response()->streamDownload(static function () use ($csv): void {
                    echo $csv;
                }, 'billing-reporting-metrics.csv', ['Content-Type' => 'text/csv']);
            }),
        ])->defaultSort('period_end', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListReportingMetrics::route('/'), 'create' => CreateReportingMetric::route('/create')];
    }
}
