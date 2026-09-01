<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Filament\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Reporting\Actions\GenerateCustomerBillingSummary;
use Liberu\Billing\Reporting\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Reporting\Filament\Resources\MetricSnapshotResource\Pages\CreateMetricSnapshot;
use Liberu\Billing\Reporting\Filament\Resources\MetricSnapshotResource\Pages\ListMetricSnapshots;
use Liberu\Billing\Reporting\Models\MetricSnapshot;

final class MetricSnapshotResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    use ScopesCurrentTeam;

    protected static ?string $model = MetricSnapshot::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required(), TextInput::make('status')->default('ready')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->actions([
            Action::make('customerSummary')->label('Customer summary')->form([
                TextInput::make('customer_id')->label('Customer ID')->numeric()->required(), TextInput::make('currency')->length(3),
            ])->action(function (array $data): void {
                $team = (int) (data_get(auth()->user(), 'current_team_id') ?? data_get(auth()->user(), 'currentTeam.id'));
                $summary = app(GenerateCustomerBillingSummary::class)->execute($team, (int) $data['customer_id'], $data['currency'] ?? null);
                session()->flash('module-billing-reporting-summary', implode(' | ', ["Invoiced: {$summary['total_invoiced']}", "Paid: {$summary['total_paid']}", "Outstanding: {$summary['total_outstanding']}", "Overdue: {$summary['overdue_amount']}"]));
            }),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMetricSnapshots::route('/'), 'create' => CreateMetricSnapshot::route('/create')];
    }
}
