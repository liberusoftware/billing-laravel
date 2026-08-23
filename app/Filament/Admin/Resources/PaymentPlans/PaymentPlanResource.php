<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentPlans;

use App\Filament\Resources\PaymentPlanResource\Pages;
use App\Models\PaymentPlan;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class PaymentPlanResource extends Resource
{
    #[Override]
    protected static ?string $model = PaymentPlan::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                [
                    Select::make('invoice_id')
                        ->relationship(
                            'invoice',
                            'invoice_number'
                        )
                        ->required(),
                    TextInput::make('total_installments')
                        ->required()
                        ->numeric()
                        ->minValue(2),
                    Select::make('frequency')
                        ->options(
                            [
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                            ]
                        )
                        ->required(),
                    DatePicker::make('start_date')
                        ->required(),
                ]
            );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('invoice.invoice_number'),
                    TextColumn::make('total_installments'),
                    TextColumn::make('installment_amount'),
                    TextColumn::make('frequency'),
                    TextColumn::make('status'),
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
                    DeleteAction::make(),
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
            // 'index' => Pages\ListPaymentPlans::route('/'),
            // 'create' => Pages\CreatePaymentPlan::route('/create'),
            // 'edit' => Pages\EditPaymentPlan::route('/{record}/edit'),
        ];
    }
}
