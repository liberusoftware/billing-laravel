<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Filament\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Liberu\Billing\Invoicing\Actions\CreatePaymentPlan;
use Liberu\Billing\Invoicing\Actions\DeliverInvoice;
use Liberu\Billing\Invoicing\Actions\FinalizeInvoice;
use Liberu\Billing\Invoicing\Actions\GenerateInvoiceDocument;
use Liberu\Billing\Invoicing\Filament\Concerns\ScopesCurrentTeam;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceResource\Pages\CreateInvoicePage;
use Liberu\Billing\Invoicing\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use Liberu\Billing\Invoicing\Models\Invoice;

final class InvoiceResource extends Resource
{
    use ScopesCurrentTeam;

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('currency')->required()->length(3), TextInput::make('customer_id')->integer()->minValue(1), TextInput::make('due_at')->type('datetime-local')]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('id')->sortable(), TextColumn::make('status')->badge(), TextColumn::make('currency'), TextColumn::make('total_minor')->label('Total')->sortable(), TextColumn::make('due_at')->dateTime()])->actions([
            Action::make('finalize')->label('Finalize')->requiresConfirmation()->visible(fn (Invoice $record): bool => $record->getRawOriginal('status') === 'draft')->action(function (Invoice $record): void {
                Gate::authorize('update', $record);
                app(FinalizeInvoice::class)->execute($record);
            }),
            Action::make('document')->label('Generate PDF')->visible(fn (Invoice $record): bool => $record->getRawOriginal('status') !== 'draft')->action(function (Invoice $record): void {
                Gate::authorize('update', $record);
                app(GenerateInvoiceDocument::class)->execute($record);
            }),
            Action::make('deliver')->label('Deliver')->visible(fn (Invoice $record): bool => $record->getRawOriginal('status') !== 'draft')->form([TextInput::make('destination')->email()->required()])->action(function (Invoice $record, array $data): void {
                Gate::authorize('update', $record);
                app(DeliverInvoice::class)->execute($record, $data['destination']);
            }),
            Action::make('paymentPlan')->label('Payment plan')->visible(fn (Invoice $record): bool => $record->getRawOriginal('status') === 'finalized')->form([TextInput::make('total_installments')->integer()->minValue(2)->required(), TextInput::make('frequency')->datalist(['weekly', 'monthly', 'quarterly'])->required()])->action(function (Invoice $record, array $data): void {
                Gate::authorize('update', $record);
                app(CreatePaymentPlan::class)->execute($record, (int) $data['total_installments'], $data['frequency']);
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListInvoices::route('/'), 'create' => CreateInvoicePage::route('/create')];
    }
}
