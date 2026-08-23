<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Users\Pages;

use App\Filament\App\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateUser extends CreateRecord
{
    #[Override]
    protected static string $resource = UserResource::class;
}
