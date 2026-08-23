<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Currencies;

use App\Filament\Admin\Resources\Currencies\Pages\CreateCurrency;
use App\Filament\Admin\Resources\Currencies\Pages\EditCurrency;
use App\Filament\Admin\Resources\Currencies\Pages\ListCurrencies;
use App\Filament\Admin\Resources\Currencies\Schemas\CurrencyForm;
use App\Filament\Admin\Resources\Currencies\Tables\CurrenciesTable;
use App\Models\Currency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class CurrencyResource extends Resource
{
    #[Override]
    protected static ?string $model = Currency::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    // Currencies are global, not team-scoped; opt out of the admin panel's tenancy.
    protected static bool $isScopedToTenant = false;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return CurrencyForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return CurrenciesTable::configure($table);
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
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }
}
