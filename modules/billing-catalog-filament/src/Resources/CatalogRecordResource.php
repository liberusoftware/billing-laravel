<?php

declare(strict_types=1);

namespace Liberu\Billing\Catalog\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

abstract class CatalogRecordResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(100),
            TextInput::make('description')->maxLength(1000),
            KeyValue::make('configuration')->keyLabel('Setting')->valueLabel('Value'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('code')->searchable(),
            TextColumn::make('status')->badge(),
        ])->defaultSort('id', 'desc');
    }
}
