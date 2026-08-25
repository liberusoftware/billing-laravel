<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Communications\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Communications\Filament\Resources\CommunicationUsageImportResource\Pages\CreateCommunicationUsageImport;
use Liberu\Billing\Communications\Filament\Resources\CommunicationUsageImportResource\Pages\ListCommunicationUsageImports;
use Liberu\Billing\Communications\Models\CommunicationUsageImport;

final class CommunicationUsageImportResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = CommunicationUsageImport::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('provider')->required()->maxLength(100), TextInput::make('rows')->required()->integer()->minValue(1), TextInput::make('total_amount_minor')->integer()->minValue(0), TextInput::make('currency')->length(3)->default('USD')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('provider'), TextColumn::make('rows'), TextColumn::make('total_amount_minor'), TextColumn::make('status')->badge()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunicationUsageImports::route('/'), 'create' => CreateCommunicationUsageImport::route('/create')];
    }
}
