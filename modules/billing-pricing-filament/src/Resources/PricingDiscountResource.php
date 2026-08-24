<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Pricing\Models\PricingDiscount;

final class PricingDiscountResource extends Resource
{
    protected static ?string $model = PricingDiscount::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('kind')->badge(), TextColumn::make('value'), TextColumn::make('redemptions'), TextColumn::make('active')->boolean()]);
    }
}
