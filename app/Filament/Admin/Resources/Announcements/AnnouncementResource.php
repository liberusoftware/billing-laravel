<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Announcements;

use App\Filament\Admin\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Admin\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Admin\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Admin\Resources\Announcements\Schemas\AnnouncementForm;
use App\Filament\Admin\Resources\Announcements\Tables\AnnouncementsTable;
use App\Models\Announcement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class AnnouncementResource extends Resource
{
    #[Override]
    protected static ?string $model = Announcement::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Content';

    // Announcements are global notices, not team-scoped; opt out of the admin panel's tenancy.
    protected static bool $isScopedToTenant = false;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
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
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
