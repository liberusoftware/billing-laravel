<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TicketDepartments;

use App\Filament\Admin\Resources\TicketDepartments\Pages\CreateTicketDepartment;
use App\Filament\Admin\Resources\TicketDepartments\Pages\EditTicketDepartment;
use App\Filament\Admin\Resources\TicketDepartments\Pages\ListTicketDepartments;
use App\Filament\Admin\Resources\TicketDepartments\Schemas\TicketDepartmentForm;
use App\Filament\Admin\Resources\TicketDepartments\Tables\TicketDepartmentsTable;
use App\Models\TicketDepartment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class TicketDepartmentResource extends Resource
{
    #[Override]
    protected static ?string $model = TicketDepartment::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Support';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return TicketDepartmentForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return TicketDepartmentsTable::configure($table);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTicketDepartments::route('/'),
            'create' => CreateTicketDepartment::route('/create'),
            'edit' => EditTicketDepartment::route('/{record}/edit'),
        ];
    }
}
