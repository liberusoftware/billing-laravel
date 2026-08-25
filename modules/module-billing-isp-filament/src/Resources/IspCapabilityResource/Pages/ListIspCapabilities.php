<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Filament\Resources\IspCapabilityResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Isp\Filament\Resources\IspCapabilityResource;

final class ListIspCapabilities extends ListRecords
{
    protected static string $resource = IspCapabilityResource::class;
}
