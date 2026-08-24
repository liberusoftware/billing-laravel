<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Payments\Models\PaymentMethod;

final class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('type')->badge(), TextColumn::make('provider'), TextColumn::make('status')->badge()]);
    }
}
