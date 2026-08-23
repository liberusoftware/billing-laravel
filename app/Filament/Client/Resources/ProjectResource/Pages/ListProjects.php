<?php

declare(strict_types=1);

namespace App\Filament\Client\Resources\ProjectResource\Pages;

use App\Filament\Client\Resources\ProjectResource;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListProjects extends ListRecords
{
    #[Override]
    protected static string $resource = ProjectResource::class;
}
