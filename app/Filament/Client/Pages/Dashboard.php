<?php

declare(strict_types=1);

namespace App\Filament\Client\Pages;

use App\Filament\Client\Widgets\ExpiringDomains;
use App\Filament\Client\Widgets\HostingOverview;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Override;

class Dashboard extends BaseDashboard
{
    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [HostingOverview::class, ExpiringDomains::class];
    }

    public function getHeading(): string
    {
        return 'Your hosting & domains';
    }
}
