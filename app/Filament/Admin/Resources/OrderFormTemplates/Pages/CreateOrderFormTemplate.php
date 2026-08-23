<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderFormTemplates\Pages;

use App\Filament\Admin\Resources\OrderFormTemplates\OrderFormTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

class CreateOrderFormTemplate extends CreateRecord
{
    #[Override]
    protected static string $resource = OrderFormTemplateResource::class;
}
