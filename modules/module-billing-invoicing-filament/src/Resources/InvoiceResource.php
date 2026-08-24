<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceResource\Pages\CreateInvoicePage;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use Liberu\Billing\Invoicing\Models\Invoice;

final class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('currency')->required()->length(3), TextInput::make('customer_id')->integer()->minValue(1), TextInput::make('due_at')->type('datetime-local')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('id')->sortable(), TextColumn::make('status')->badge(), TextColumn::make('currency'), TextColumn::make('total_minor')->label('Total')->sortable(), TextColumn::make('due_at')->dateTime()])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListInvoices::route('/'), 'create' => CreateInvoicePage::route('/create')];
    }
}
