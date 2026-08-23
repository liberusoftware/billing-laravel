<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\MenuResource\Pages;

use App\Filament\Admin\Resources\MenuResource;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditMenu extends EditRecord
{
    #[Override]
    protected static string $resource = MenuResource::class;
}
