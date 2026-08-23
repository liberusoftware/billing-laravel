<?php

namespace App\Filament\App\Resources\PartialPayments;

use App\Filament\App\Resources\PartialPayments\Pages\CreatePartialPayment;
use App\Filament\App\Resources\PartialPayments\Pages\EditPartialPayment;
use App\Filament\App\Resources\PartialPayments\Pages\ListPartialPayments;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

class PartialPaymentResource extends Resource
{
    #[Override]
    protected static ?string $model = Invoice::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Select::make('invoice_id')
                        ->label('Invoice')
                        ->options(
                            Invoice::where(
                                'status',
                                'pending'
                            )->orWhere(
                                'status',
                                'partially_paid'
                            )->pluck(
                                'invoice_number',
                                'id'
                            )
                        )
                        ->required()
                        ->searchable(),
                    TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->label('Payment Amount'),
                    Select::make('payment_gateway_id')
                        ->label('Payment Gateway')
                        ->options(
                            PaymentGateway::where(
                                'is_active',
                                true
                            )->pluck(
                                'name',
                                'id'
                            )
                        )
                        ->required()
                        ->searchable(),
                ]
            );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('invoice_number')->sortable(),
                    TextColumn::make('total_amount')->sortable(),
                    TextColumn::make('paid_amount')->sortable(),
                    TextColumn::make('remaining_amount')->sortable(),
                    TextColumn::make('status')->sortable(),
                ]
            )
            ->filters(
                [
                    //
                ]
            )
            ->recordActions(
                [
                    EditAction::make(),
                ]
            )
            ->toolbarActions(
                [
                    DeleteBulkAction::make(),
                ]
            );
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListPartialPayments::route('/'),
            'create' => CreatePartialPayment::route('/create'),
            'edit' => EditPartialPayment::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(
                function ($query): void {
                    $query->where(
                        'status',
                        'pending'
                    )
                        ->orWhere(
                            'status',
                            'partially_paid'
                        );
                }
            )
            ->latest();
    }
}
