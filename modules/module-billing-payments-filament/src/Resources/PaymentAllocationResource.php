<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Payments\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Payments\Filament\Resources\PaymentAllocationResource\Pages\ListPaymentAllocations;
use Liberu\Billing\Payments\Models\PaymentAllocation;

final class PaymentAllocationResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = PaymentAllocation::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('payment_id'), TextColumn::make('invoice_id'), TextColumn::make('amount_minor'), TextColumn::make('currency')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPaymentAllocations::route('/')];
    }
}
