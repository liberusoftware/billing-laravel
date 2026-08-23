<?php

namespace App\Filament\App\Resources\Refunds;

use App\Filament\App\Resources\Refunds\Pages\CreateRefund;
use App\Filament\App\Resources\Refunds\Pages\EditRefund;
use App\Filament\App\Resources\Refunds\Pages\ListRefunds;
use App\Models\Payment;
use App\Services\RefundService;
use BackedEnum;
use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class RefundResource extends Resource
{
    #[Override]
    protected static ?string $model = Payment::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    #[Override]
    protected static ?string $navigationLabel = 'Refunds';

    #[Override]
    protected static ?string $modelLabel = 'Refund';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Select::make('payment_id')
                        ->label('Payment')
                        ->options(
                            fn () => Payment::whereIn(
                                'refund_status',
                                [
                                    'none',
                                    'partial',
                                ]
                            )
                                ->with('invoice')
                                ->get()
                                ->mapWithKeys(
                                    fn ($payment): array => [
                                        $payment->id => "Payment #{$payment->id} - Invoice #{$payment->invoice->invoice_number} ({$payment->amount} {$payment->currency})",
                                    ]
                                )
                        )
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(
                            function ($state, callable $set): void {
                                if ($state) {
                                    $payment = Payment::find($state);
                                    $set(
                                        'max_refund_amount',
                                        $payment->getRemainingRefundableAmount()
                                    );
                                    $set(
                                        'currency',
                                        $payment->currency
                                    );
                                }
                            }
                        ),

                    TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->label('Refund Amount')
                        ->hint(fn ($state, $record): string => $record ? "Maximum refundable amount: {$record->getRemainingRefundableAmount()} {$record->currency}" : '')
                        ->rules(
                            [
                                'required',
                                'numeric',
                                'min:0.01',
                                function (string $attribute, $value, Closure $fail): void {
                                    $payment = Payment::find(request()->input('data.payment_id'));
                                    if ($payment && $value > $payment->getRemainingRefundableAmount()) {
                                        $fail("The refund amount cannot exceed the remaining refundable amount of {$payment->getRemainingRefundableAmount()} {$payment->currency}");
                                    }
                                },
                            ]
                        ),

                    Textarea::make('reason')
                        ->label('Refund Reason')
                        ->required()
                        ->maxLength(1000),

                    Hidden::make('currency'),
                ]
            );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('id')
                        ->label('Payment ID')
                        ->sortable(),
                    TextColumn::make('invoice.invoice_number')
                        ->label('Invoice Number')
                        ->sortable()
                        ->searchable(),
                    TextColumn::make('amount')
                        ->label('Original Amount')
                        ->money(fn ($record) => $record->currency)
                        ->sortable(),
                    TextColumn::make('refunded_amount')
                        ->label('Refunded Amount')
                        ->money(fn ($record) => $record->currency)
                        ->sortable(),
                    TextColumn::make('refund_status')
                        ->badge()
                        ->color(
                            fn (string $state): string => match ($state) {
                                'none' => 'danger',
                                'partial' => 'warning',
                                'full' => 'success',
                                default => 'gray',
                            }
                        ),
                    TextColumn::make('created_at')
                        ->label('Payment Date')
                        ->dateTime()
                        ->sortable(),
                ]
            )
            ->filters(
                [
                    SelectFilter::make('refund_status')
                        ->options(
                            [
                                'none' => 'No Refund',
                                'partial' => 'Partially Refunded',
                                'full' => 'Fully Refunded',
                            ]
                        ),
                ]
            )
            ->recordActions(
                [
                    Action::make('refund')
                        ->visible(fn (Payment $record): bool => $record->isRefundable())
                        ->schema(
                            [
                                TextInput::make('amount')
                                    ->required()
                                    ->numeric()
                                    ->label('Refund Amount')
                                    ->rules(
                                        fn (Payment $record): array => [
                                            'required',
                                            'numeric',
                                            'min:0.01',
                                            function (string $attribute, $value, Closure $fail) use ($record): void {
                                                if ($value > $record->getRemainingRefundableAmount()) {
                                                    $fail("Cannot refund more than {$record->getRemainingRefundableAmount()} {$record->currency}");
                                                }
                                            },
                                        ]
                                    ),
                                Textarea::make('reason')
                                    ->required()
                                    ->label('Refund Reason'),
                            ]
                        )
                        ->action(
                            function (array $data, Payment $record): void {
                                $refundService = app(RefundService::class);

                                try {
                                    $result = $refundService->processRefund(
                                        $record,
                                        $data['amount']
                                    );

                                    if ($result['success']) {
                                        Notification::make()
                                            ->title('Refund processed successfully')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Refund failed')
                                            ->body($result['message'])
                                            ->danger()
                                            ->send();
                                    }
                                } catch (Exception $e) {
                                    Notification::make()
                                        ->title('Error processing refund')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }
                        ),
                ]
            )
            ->toolbarActions([])
            ->defaultSort(
                'created_at',
                'desc'
            );
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListRefunds::route('/'),
            'create' => CreateRefund::route('/create'),
            'edit' => EditRefund::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(
                [
                    'invoice',
                    'paymentGateway',
                ]
            )
            ->latest();
    }
}
