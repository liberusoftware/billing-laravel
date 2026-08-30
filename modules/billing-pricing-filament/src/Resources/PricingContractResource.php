<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Pricing\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Pricing\Filament\Resources\PricingContractResource\Pages\CreatePricingContract;
use Liberu\Billing\Pricing\Filament\Resources\PricingContractResource\Pages\ListPricingContracts;
use Liberu\Billing\Pricing\Models\PricingContract;

final class PricingContractResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = PricingContract::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('pricing_plan_id')->required()->integer()->minValue(1),
            TextInput::make('customer_id')->integer()->minValue(1),
            DateTimePicker::make('starts_at')->required()->default(now()),
            DateTimePicker::make('ends_at'),
            TextInput::make('status')->required()->default('active')->datalist(['active', 'ended', 'cancelled']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('pricing_plan_id'), TextColumn::make('customer_id'), TextColumn::make('status')->badge(), TextColumn::make('starts_at')->dateTime()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPricingContracts::route('/'), 'create' => CreatePricingContract::route('/create')];
    }
}
