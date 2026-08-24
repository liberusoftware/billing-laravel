<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Domains\Models\DomainContact;

final class DomainContactResource extends Resource
{
    protected static ?string $model = DomainContact::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('handle')->required(), TextInput::make('name')->required(), TextInput::make('email')->required()->email()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('handle')->searchable(), TextColumn::make('name'), TextColumn::make('email')->searchable()]);
    }
}
