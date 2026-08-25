<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Pricing\Actions\RedeemPricingDiscount;
use Liberu\Billing\Pricing\Models\PricingDiscount;

final class PricingDiscountResource extends Resource
{
    protected static ?string $model = PricingDiscount::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('code')->searchable(), TextColumn::make('kind')->badge(), TextColumn::make('value'), TextColumn::make('redemptions'), TextColumn::make('active')->boolean()])->actions([
            Action::make('redeem')->label('Redeem')->requiresConfirmation()->visible(fn (PricingDiscount $record): bool => (bool) $record->active)->action(function (PricingDiscount $record, RedeemPricingDiscount $redeem): void {
                Gate::authorize('update', $record);
                $redeem->execute($record);
            }),
        ]);
    }
}
