<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Filament\Resources\HostingCapabilityResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Hosting\Filament\Resources\HostingCapabilityResource;

final class ListHostingCapabilities extends ListRecords
{
    protected static string $resource = HostingCapabilityResource::class;
}
