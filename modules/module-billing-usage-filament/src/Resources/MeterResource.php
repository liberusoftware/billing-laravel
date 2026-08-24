<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Usage\Filament\Resources\MeterResource\Pages\CreateMeter;
use Liberu\Billing\Usage\Filament\Resources\MeterResource\Pages\ListMeters;
use Liberu\Billing\Usage\Models\Meter;

final class MeterResource extends Resource
{
    protected static ?string $model = Meter::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->maxLength(255),
            TextInput::make('code')->required()->maxLength(100),
            TextInput::make('unit')->required()->maxLength(50),
            TextInput::make('unit_price_minor')->required()->integer()->minValue(0),
            TextInput::make('currency')->required()->length(3)->alpha(),
            TextInput::make('threshold')->numeric()->minValue(0),
            Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->searchable()->sortable(),
            TextColumn::make('name'),
            TextColumn::make('unit'),
            TextColumn::make('unit_price_minor'),
            TextColumn::make('currency'),
            TextColumn::make('active')->badge(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListMeters::route('/'), 'create' => CreateMeter::route('/create')];
    }
}
