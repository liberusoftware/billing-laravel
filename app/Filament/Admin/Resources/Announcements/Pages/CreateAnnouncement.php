<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Announcements\Pages;

use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateAnnouncement extends CreateRecord
{
    #[Override]
    protected static string $resource = AnnouncementResource::class;
}
