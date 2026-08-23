<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketCustomFields;

use App\Filament\Admin\Resources\TicketCustomFields\Pages\CreateTicketCustomField;
use App\Filament\Admin\Resources\TicketCustomFields\Pages\EditTicketCustomField;
use App\Filament\Admin\Resources\TicketCustomFields\Pages\ListTicketCustomFields;
use App\Filament\Admin\Resources\TicketCustomFields\Schemas\TicketCustomFieldForm;
use App\Filament\Admin\Resources\TicketCustomFields\Tables\TicketCustomFieldsTable;
use App\Models\TicketCustomField;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class TicketCustomFieldResource extends Resource
{
    #[Override]
    protected static ?string $model = TicketCustomField::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Support';

    #[Override]
    protected static ?string $navigationLabel = 'Ticket Fields';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return TicketCustomFieldForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return TicketCustomFieldsTable::configure($table);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTicketCustomFields::route('/'),
            'create' => CreateTicketCustomField::route('/create'),
            'edit' => EditTicketCustomField::route('/{record}/edit'),
        ];
    }
}
