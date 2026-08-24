<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Invoicing\Models\InvoiceSchedule;

final class InvoiceScheduleResource extends Resource
{
    protected static ?string $model = InvoiceSchedule::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('frequency')->required(), TextInput::make('next_run_at')->type('datetime-local'), TextInput::make('active')->boolean()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('frequency')->badge(), TextColumn::make('next_run_at')->dateTime(), TextColumn::make('active')->boolean()]);
    }
}
