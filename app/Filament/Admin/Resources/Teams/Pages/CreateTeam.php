<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Teams\Pages;

use App\Filament\Admin\Resources\Teams\TeamResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateTeam extends CreateRecord
{
    #[Override]
    protected static string $resource = TeamResource::class;
}
