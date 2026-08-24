<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Communications\Models\CommunicationNumber;

final class CommunicationNumberResource extends Resource
{
    protected static ?string $model = CommunicationNumber::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('number')->required(), TextInput::make('type')->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('number')->searchable(), TextColumn::make('type'), TextColumn::make('status')->badge()]);
    }
}
