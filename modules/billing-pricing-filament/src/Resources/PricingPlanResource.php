<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Pricing\Models\PricingPlan;

final class PricingPlanResource extends Resource
{
    protected static ?string $model = PricingPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), TextInput::make('pricing_model')->required(), TextInput::make('unit_amount_minor')->required()->integer()->minValue(0), TextInput::make('currency')->required()->length(3)->default('USD')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('pricing_model')->badge(), TextColumn::make('unit_amount_minor'), TextColumn::make('currency')->badge(), TextColumn::make('status')->badge()])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [];
    }
}
