<?php

declare(strict_types=1);

namespace App\Filament\Client\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Override;

class Dashboard extends BaseDashboard
{
    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';
}
