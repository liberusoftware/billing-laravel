<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderFormTemplates;

use App\Filament\Admin\Resources\OrderFormTemplates\Pages\CreateOrderFormTemplate;
use App\Filament\Admin\Resources\OrderFormTemplates\Pages\EditOrderFormTemplate;
use App\Filament\Admin\Resources\OrderFormTemplates\Pages\ListOrderFormTemplates;
use App\Filament\Admin\Resources\OrderFormTemplates\Schemas\OrderFormTemplateForm;
use App\Filament\Admin\Resources\OrderFormTemplates\Tables\OrderFormTemplatesTable;
use App\Models\OrderFormTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class OrderFormTemplateResource extends Resource
{
    #[Override]
    protected static ?string $model = OrderFormTemplate::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    // Order form templates are a global catalog object, not team-scoped; opt out of the admin panel's tenancy.
    protected static bool $isScopedToTenant = false;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return OrderFormTemplateForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return OrderFormTemplatesTable::configure($table);
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
            'index' => ListOrderFormTemplates::route('/'),
            'create' => CreateOrderFormTemplate::route('/create'),
            'edit' => EditOrderFormTemplate::route('/{record}/edit'),
        ];
    }
}
