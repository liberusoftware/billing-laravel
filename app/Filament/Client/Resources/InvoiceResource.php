<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Client\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class InvoiceResource extends Resource
{
    #[Override]
    protected static ?string $model = Invoice::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    #[Override]
    protected static ?string $navigationLabel = 'Invoices';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Section::make()
                        ->schema(
                            [
                                Grid::make(2)
                                    ->schema(
                                        [
                                            TextInput::make('invoice_number')
                                                ->disabled()
                                                ->label('Invoice Number'),
                                            DatePicker::make('issue_date')
                                                ->disabled(),
                                            DatePicker::make('due_date')
                                                ->disabled(),
                                            TextInput::make('total_amount')
                                                ->disabled()
                                                ->prefix(fn (Invoice $record) => $record->currency),
                                            TextInput::make('status')
                                                ->disabled(),
                                            TextInput::make('remaining_amount')
                                                ->disabled()
                                                ->prefix(fn (Invoice $record) => $record->currency)
                                                ->visible(fn (Invoice $record): bool => $record->status === 'partially_paid'),
                                        ]
                                    ),
                            ]
                        ),

                    Section::make()
                        ->visible(fn (Invoice $record): bool => $record->status !== 'paid')
                        ->schema(
                            [
                                Select::make('payment_method')
                                    ->options(
                                        [
                                            'credit_card' => 'Credit Card',
                                            'bank_transfer' => 'Bank Transfer',
                                            'paypal' => 'PayPal',
                                        ]
                                    )
                                    ->required(),
                                TextInput::make('payment_amount')
                                    ->numeric()
                                    ->required()
                                    ->rules(
                                        [
                                            fn (Invoice $record): string => 'max:'.$record->remaining_amount,
                                            'min:1',
                                        ]
                                    ),
                            ]
                        ),
                ]
            );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('invoice_number')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('total_amount')
                        ->money(fn (Invoice $record) => $record->currency)
                        ->sortable(),
                    TextColumn::make('status')
                        ->badge()
                        ->color(
                            fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'paid' => 'success',
                                'overdue' => 'danger',
                                'partially_paid' => 'info',
                                default => 'gray',
                            }
                        ),
                    TextColumn::make('due_date')
                        ->date()
                        ->sortable(),
                ]
            )
            ->filters(
                [
                    SelectFilter::make('status')
                        ->options(
                            [
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'partially_paid' => 'Partially Paid',
                            ]
                        ),
                ]
            )
            ->recordActions(
                [
                    ViewAction::make(),
                    Action::make('pay')
                        ->icon('heroicon-o-credit-card')
                        ->visible(fn (Invoice $record): bool => $record->status !== 'paid')
                        ->action(
                            function (Invoice $record, array $data): void {
                                $record->processPayment(
                                    $data['payment_method'],
                                    $data['payment_amount']
                                );
                            }
                        )
                        ->schema(
                            [
                                Select::make('payment_method')
                                    ->options(
                                        [
                                            'credit_card' => 'Credit Card',
                                            'bank_transfer' => 'Bank Transfer',
                                            'paypal' => 'PayPal',
                                        ]
                                    )
                                    ->required(),
                                TextInput::make('payment_amount')
                                    ->numeric()
                                    ->required()
                                    ->rules(
                                        [
                                            fn (Invoice $record): string => 'max:'.$record->remaining_amount,
                                            'min:1',
                                        ]
                                    ),
                            ]
                        ),
                    Action::make('download_pdf')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(
                            fn (Invoice $record) => response()->streamDownload(
                                fn (): int => print ($record->generatePdf()->output()),
                                "invoice-{$record->invoice_number}.pdf"
                            )
                        ),
                ]
            )
            ->defaultSort(
                'created_at',
                'desc'
            );
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        // Invoices belong to a Customer (no client_id column exists). The client
        // panel authenticates a User, so scope to invoices of the Customer whose
        // email matches the logged-in user.
        return parent::getEloquentQuery()->whereHas(
            'customer',
            fn (Builder $query) => $query->where('email', auth()->user()->email)
        );
    }
}
