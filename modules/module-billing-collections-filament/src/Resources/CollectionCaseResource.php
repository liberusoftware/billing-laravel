<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource\Pages\CreateCollectionCase;
use Liberu\Billing\Collections\Filament\Resources\CollectionCaseResource\Pages\ListCollectionCases;
use Liberu\Billing\Collections\Models\CollectionCase;

final class CollectionCaseResource extends Resource
{
    protected static ?string $model = CollectionCase::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('amount_minor')->required()->integer()->minValue(1), TextInput::make('currency')->required()->length(3), TextInput::make('customer_id')->integer()->minValue(1), TextInput::make('type')->default('dunning')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('id')->sortable(), TextColumn::make('status')->badge(), TextColumn::make('type'), TextColumn::make('amount_minor'), TextColumn::make('currency')])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListCollectionCases::route('/'), 'create' => CreateCollectionCase::route('/create')];
    }
}
