<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Affiliates\Pages;

use App\Filament\App\Resources\Affiliates\AffiliateResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateAffiliate extends CreateRecord
{
    #[Override]
    protected static string $resource = AffiliateResource::class;
}
