<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceSupportResource\Pages\CreateInvoiceSupport;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceSupportResource\Pages\ListInvoiceSupports;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final class InvoiceSupportResource extends Resource
{
    protected static ?string $model = InvoiceSupport::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('invoice_id')->required()->integer()->minValue(1), TextInput::make('type')->required()->in(['tax', 'credit', 'adjustment', 'pdf', 'delivery']), TextInput::make('amount_minor')->integer()->minValue(0), TextInput::make('destination')->email()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('invoice_id'), TextColumn::make('amount_minor'), TextColumn::make('status')->badge(), TextColumn::make('destination')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListInvoiceSupports::route('/'), 'create' => CreateInvoiceSupport::route('/create')];
    }
}
