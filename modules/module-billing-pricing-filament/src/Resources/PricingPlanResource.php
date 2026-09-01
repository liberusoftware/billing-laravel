<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Pricing\Actions\CalculateUsageBasedPrice;
use Liberu\Billing\Pricing\Actions\CapturePricingSnapshot;
use Liberu\Billing\Pricing\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Pricing\Filament\Resources\PricingPlanResource\Pages\CreatePricingPlan;
use Liberu\Billing\Pricing\Filament\Resources\PricingPlanResource\Pages\ListPricingPlans;
use Liberu\Billing\Pricing\Models\PricingPlan;

final class PricingPlanResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = PricingPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog & Pricing';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('pricing_model')->required()->options([
                'recurring' => 'Recurring',
                'one_time' => 'One time',
                'usage' => 'Usage',
                'tiered' => 'Tiered',
            ]),
            TextInput::make('unit_amount_minor')->required()->integer()->minValue(0),
            TextInput::make('currency')->required()->length(3)->default('USD'),
            TextInput::make('billing_interval')->maxLength(30),
            TextInput::make('usage_unit')->maxLength(50),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('pricing_model')->badge(), TextColumn::make('unit_amount_minor'), TextColumn::make('currency')->badge(), TextColumn::make('status')->badge()])->actions([
            Action::make('snapshot')->label('Capture snapshot')->requiresConfirmation()->action(function (PricingPlan $record, CapturePricingSnapshot $capture): void {
                Gate::authorize('update', $record);
                $capture->execute($record);
            }),
            Action::make('usage')->label('Calculate usage')->visible(fn (PricingPlan $record): bool => in_array($record->pricing_model->value, ['usage', 'tiered'], true))->form([
                TextInput::make('meter_id')->required()->integer()->minValue(1),
                TextInput::make('customer_id')->integer()->minValue(1),
                DateTimePicker::make('start')->required(),
                DateTimePicker::make('end')->required(),
            ])->action(function (PricingPlan $record, array $data, CalculateUsageBasedPrice $calculate): void {
                Gate::authorize('view', $record);
                $result = $calculate->execute($record, (int) $data['meter_id'], $data['start'], $data['end'], isset($data['customer_id']) ? (int) $data['customer_id'] : null);
                Notification::make()->title(__('Usage price: :amount', ['amount' => $result['amount_minor']]))->success()->send();
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPricingPlans::route('/'),
            'create' => CreatePricingPlan::route('/create'),
        ];
    }
}
