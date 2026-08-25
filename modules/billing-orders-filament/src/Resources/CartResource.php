<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Orders\Models\Cart;

final class CartResource extends Resource
{
    protected static ?string $model = Cart::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('customer_id'), TextColumn::make('currency'), TextColumn::make('status')->badge(), TextColumn::make('expires_at')->dateTime()]);
    }
}
