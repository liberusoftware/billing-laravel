<?php

declare(strict_types=1);

namespace Liberu\Billing\CustomerPortal\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalRequestResource\Pages\CreatePortalRequest;
use Liberu\Billing\CustomerPortal\Filament\Resources\PortalRequestResource\Pages\ListPortalRequests;
use Liberu\Billing\CustomerPortal\Models\PortalRequest;

final class PortalRequestResource extends Resource
{
    protected static ?string $model = PortalRequest::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()->maxLength(255), TextInput::make('status')->required()->default('open')->maxLength(32)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable(), TextColumn::make('status')->badge(), TextColumn::make('created_at')->dateTime()])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListPortalRequests::route('/'), 'create' => CreatePortalRequest::route('/create')];
    }
}
