<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Licenses;

use App\Filament\Admin\Resources\Licenses\Pages\CreateLicense;
use App\Filament\Admin\Resources\Licenses\Pages\EditLicense;
use App\Filament\Admin\Resources\Licenses\Pages\ListLicenses;
use App\Filament\Admin\Resources\Licenses\Schemas\LicenseForm;
use App\Filament\Admin\Resources\Licenses\Tables\LicensesTable;
use App\Models\License;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class LicenseResource extends Resource
{
    #[Override]
    protected static ?string $model = License::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Licensing';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return LicenseForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return LicensesTable::configure($table);
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
            'index' => ListLicenses::route('/'),
            'create' => CreateLicense::route('/create'),
            'edit' => EditLicense::route('/{record}/edit'),
        ];
    }
}
