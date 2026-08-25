<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Domains\Filament\Resources\DomainTldResource\Pages\CreateDomainTld;
use Liberu\Billing\Domains\Filament\Resources\DomainTldResource\Pages\ListDomainTlds;
use Liberu\Billing\Domains\Models\DomainTld;

final class DomainTldResource extends Resource
{
    protected static ?string $model = DomainTld::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(64),
            TextInput::make('registrar_cost')->numeric()->minValue(0),
            TextInput::make('base_price')->numeric()->minValue(0)->required(),
            Select::make('markup_type')->options(['none' => 'None', 'percentage' => 'Percentage', 'fixed' => 'Fixed'])->required()->default('none'),
            TextInput::make('markup_value')->numeric()->minValue(0)->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('base_price'), TextColumn::make('markup_type')->badge(), TextColumn::make('markup_value'), TextColumn::make('enabled')->boolean()])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => ListDomainTlds::route('/'), 'create' => CreateDomainTld::route('/create')];
    }
}
