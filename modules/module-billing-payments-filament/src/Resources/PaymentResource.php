<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Payments\Actions\AllocatePayment;
use Liberu\Billing\Payments\Actions\CapturePayment;
use Liberu\Billing\Payments\Actions\OpenDispute;
use Liberu\Billing\Payments\Actions\ReconcilePayment;
use Liberu\Billing\Payments\Actions\RefundPayment;
use Liberu\Billing\Payments\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Payments\Filament\Resources\PaymentResource\Pages\CreatePayment;
use Liberu\Billing\Payments\Filament\Resources\PaymentResource\Pages\ListPayments;
use Liberu\Billing\Payments\Models\Payment;

final class PaymentResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount_minor')->required()->integer()->minValue(1),
            TextInput::make('currency')->required()->length(3)->alpha()->default('USD'),
            TextInput::make('gateway')->maxLength(100),
            TextInput::make('payment_method')->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('amount_minor')->sortable(),
            TextColumn::make('currency')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('gateway'),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([
            Action::make('capture')->label('Capture')->visible(fn (Payment $record): bool => $record->getRawOriginal('status') === 'pending')->action(function (Payment $record): void {
                Gate::authorize('update', $record);
                app(CapturePayment::class)->execute($record);
            }),
            Action::make('allocate')->label('Allocate to invoice')->visible(fn (Payment $record): bool => in_array($record->getRawOriginal('status'), ['captured', 'disputed'], true))->form([TextInput::make('invoice_id')->integer()->minValue(1)->required(), TextInput::make('amount_minor')->integer()->minValue(1)->required()])->action(function (Payment $record, array $data): void {
                Gate::authorize('update', $record);
                app(AllocatePayment::class)->execute($record, (int) $data['amount_minor'], (int) $data['invoice_id']);
            }),
            Action::make('refund')->label('Refund')->visible(fn (Payment $record): bool => $record->getRawOriginal('status') === 'captured')->form([TextInput::make('amount_minor')->integer()->minValue(1)->required(), TextInput::make('reason')->required()->maxLength(255)])->action(function (Payment $record, array $data): void {
                Gate::authorize('update', $record);
                app(RefundPayment::class)->execute($record, (int) $data['amount_minor'], $data['reason']);
            }),
            Action::make('dispute')->label('Open dispute')->visible(fn (Payment $record): bool => $record->getRawOriginal('status') === 'captured')->form([TextInput::make('amount_minor')->integer()->minValue(1)->required(), TextInput::make('reason')->required()->maxLength(255)])->action(function (Payment $record, array $data): void {
                Gate::authorize('update', $record);
                app(OpenDispute::class)->execute($record, (int) $data['amount_minor'], $data['reason']);
            }),
            Action::make('reconcile')->label('Reconcile')->form([TextInput::make('provider_reference')->required()->maxLength(255)])->action(function (Payment $record, array $data): void {
                Gate::authorize('update', $record);
                app(ReconcilePayment::class)->execute($record, $data['provider_reference']);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/'), 'create' => CreatePayment::route('/create')];
    }
}
