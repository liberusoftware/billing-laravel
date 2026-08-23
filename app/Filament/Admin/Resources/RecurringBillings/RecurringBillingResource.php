<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\RecurringBillings;

use App\Filament\Resources\RecurringBillingResource\Pages;
use App\Models\RecurringBillingConfiguration;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class RecurringBillingResource extends Resource
{
    #[Override]
    protected static ?string $model = RecurringBillingConfiguration::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-refresh';

    #[Override]
    protected static ?string $navigationLabel = 'Recurring Billing';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Billing';

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
                        ->required()
                        ->searchable(),
                    Select::make('frequency')
                        ->options(
                            [
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'yearly' => 'Yearly',
                            ]
                        )
                        ->required(),
                    TextInput::make('billing_day')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(31)
                        ->helperText('Day of the month when billing should occur'),
                    DatePicker::make('next_billing_date')
                        ->required(),
                    Toggle::make('is_active')
                        ->required()
                        ->default(true),
                ]
            );
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                [
                    TextColumn::make('invoice.invoice_number')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('frequency')
                        ->sortable(),
                    TextColumn::make('billing_day')
                        ->sortable(),
                    TextColumn::make('next_billing_date')
                        ->date()
                        ->sortable(),
                    IconColumn::make('is_active')
                        ->boolean()
                        ->sortable(),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                ]
            )
            ->filters(
                [
                    SelectFilter::make('frequency')
                        ->options(
                            [
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'yearly' => 'Yearly',
                            ]
                        ),
                    Filter::make('is_active'),
                ]
            )
            ->recordActions(
                [
                    EditAction::make(),
                    DeleteAction::make(),
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
            // 'index' => Pages\ListRecurringBillings::route('/'),
            // 'create' => Pages\CreateRecurringBilling::route('/create'),
            // 'edit' => Pages\EditRecurringBilling::route('/{record}/edit'),
        ];
    }
}
