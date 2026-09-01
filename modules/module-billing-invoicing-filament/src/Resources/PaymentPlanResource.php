<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources;

use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Liberu\Billing\Invoicing\Actions\RunPaymentPlan;
use Liberu\Billing\Invoicing\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Invoicing\Models\PaymentPlan;

final class PaymentPlanResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Billing Operations';

    use ScopesCurrentTeam;

    protected static ?string $model = PaymentPlan::class;

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('invoice_id'), TextColumn::make('frequency')->badge(), TextColumn::make('generated_installments')->label('Generated'), TextColumn::make('total_installments')->label('Total'), TextColumn::make('next_due_at')->dateTime(), TextColumn::make('status')->badge()])->actions([
            Action::make('run')->label('Run')->visible(fn (PaymentPlan $record): bool => $record->status === 'active')->requiresConfirmation()->action(fn (PaymentPlan $record): mixed => app(RunPaymentPlan::class)->execute($record)),
        ])->defaultSort('id', 'desc');
    }
}
