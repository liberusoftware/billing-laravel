<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Payments\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Payments\Filament\Resources\PaymentReconciliationResource\Pages\ListPaymentReconciliations;
use Liberu\Billing\Payments\Models\PaymentReconciliation;

final class PaymentReconciliationResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = PaymentReconciliation::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('payment_id'), TextColumn::make('provider_reference'), TextColumn::make('status')->badge()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPaymentReconciliations::route('/')];
    }
}
