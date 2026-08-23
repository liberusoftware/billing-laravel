<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentGateways;

use App\Filament\Admin\Resources\PaymentGateways\Pages\CreatePaymentGateway;
use App\Filament\Admin\Resources\PaymentGateways\Pages\EditPaymentGateway;
use App\Filament\Admin\Resources\PaymentGateways\Pages\ListPaymentGateways;
use App\Filament\Admin\Resources\PaymentGateways\Schemas\PaymentGatewayForm;
use App\Filament\Admin\Resources\PaymentGateways\Tables\PaymentGatewaysTable;
use App\Models\PaymentGateway;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class PaymentGatewayResource extends Resource
{
    #[Override]
    protected static ?string $model = PaymentGateway::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return PaymentGatewayForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return PaymentGatewaysTable::configure($table);
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
            'index' => ListPaymentGateways::route('/'),
            'create' => CreatePaymentGateway::route('/create'),
            'edit' => EditPaymentGateway::route('/{record}/edit'),
        ];
    }
}
