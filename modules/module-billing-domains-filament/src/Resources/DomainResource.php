<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Domains\Filament\Resources\DomainResource\Pages\CreateDomain as CreateDomainPage;
use Liberu\Billing\Domains\Filament\Resources\DomainResource\Pages\ListDomains;
use Liberu\Billing\Domains\Models\Domain;

final class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('status')->required()->maxLength(50)->default('active'),
            TextInput::make('registrar')->maxLength(100),
            TextInput::make('transfer_status')->maxLength(50),
            TextInput::make('expires_at')->type('datetime-local'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('registrar'),
            TextColumn::make('expires_at')->dateTime()->sortable(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListDomains::route('/'), 'create' => CreateDomainPage::route('/create')];
    }
}
