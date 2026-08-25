<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Orders\Models\Quote;

final class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('quote_number')->searchable(), TextColumn::make('total_minor'), TextColumn::make('currency'), TextColumn::make('status')->badge()]);
    }
}
