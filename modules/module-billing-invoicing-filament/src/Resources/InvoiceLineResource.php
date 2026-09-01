<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Invoicing\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Invoicing\Models\InvoiceLine;

final class InvoiceLineResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    use ScopesCurrentTeam;

    protected static ?string $model = InvoiceLine::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('invoice_id'), TextColumn::make('description')->searchable(), TextColumn::make('quantity'), TextColumn::make('amount_minor')]);
    }
}
