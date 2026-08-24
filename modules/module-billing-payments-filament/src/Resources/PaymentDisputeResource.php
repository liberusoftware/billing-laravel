<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Payments\Models\PaymentDispute;

final class PaymentDisputeResource extends Resource
{
    protected static ?string $model = PaymentDispute::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('payment_id'), TextColumn::make('amount_minor'), TextColumn::make('status')->badge(), TextColumn::make('reason')]);
    }
}
