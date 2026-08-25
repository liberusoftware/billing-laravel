<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Pricing\Actions\CapturePricingSnapshot;
use Liberu\Billing\Pricing\Filament\Resources\PricingPlanResource\Pages\CreatePricingPlan;
use Liberu\Billing\Pricing\Filament\Resources\PricingPlanResource\Pages\ListPricingPlans;
use Liberu\Billing\Pricing\Models\PricingPlan;

final class PricingPlanResource extends Resource
{
    protected static ?string $model = PricingPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

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
