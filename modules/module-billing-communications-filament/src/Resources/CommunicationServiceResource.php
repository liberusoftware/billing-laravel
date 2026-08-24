<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Communications\Models\CommunicationService;

final class CommunicationServiceResource extends Resource
{
    protected static ?string $model = CommunicationService::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), TextInput::make('status')->required()->default('active')->maxLength(32)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->sortable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->defaultSort('id', 'desc');
    }
}
