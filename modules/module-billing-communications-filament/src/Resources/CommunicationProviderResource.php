<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Communications\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Communications\Filament\Resources\CommunicationProviderResource\Pages\CreateCommunicationProvider;
use Liberu\Billing\Communications\Filament\Resources\CommunicationProviderResource\Pages\ListCommunicationProviders;
use Liberu\Billing\Communications\Models\CommunicationProvider;

final class CommunicationProviderResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = CommunicationProvider::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('driver')->required()->maxLength(100),
            TextInput::make('status')->required()->default('active')->maxLength(32),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('driver')->badge(),
            TextColumn::make('status')->badge(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunicationProviders::route('/'), 'create' => CreateCommunicationProvider::route('/create')];
    }
}
