<?php

declare(strict_types=1);

namespace App\Filament\Client\Pages;

use App\Models\Announcement;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Override;

class Announcements extends Page
{
    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    #[Override]
    protected string $view = 'filament.client.pages.announcements';

    /**
     * @return Collection<int, Announcement>
     */
    public function getAnnouncementsProperty(): Collection
    {
        return Announcement::active()->latest('published_at')->get();
    }
}
