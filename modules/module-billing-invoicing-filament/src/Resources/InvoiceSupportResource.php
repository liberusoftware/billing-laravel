<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Invoicing\Models\InvoiceSupport;

final class InvoiceSupportResource extends Resource
{
    protected static ?string $model = InvoiceSupport::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('invoice_id'), TextColumn::make('amount_minor'), TextColumn::make('status')->badge(), TextColumn::make('destination')]);
    }
}
