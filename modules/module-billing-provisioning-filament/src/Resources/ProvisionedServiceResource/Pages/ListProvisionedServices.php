<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Filament\Resources\ProvisionedServiceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Liberu\Billing\Provisioning\Filament\Resources\ProvisionedServiceResource;

final class ListProvisionedServices extends ListRecords
{
    protected static string $resource = ProvisionedServiceResource::class;
}
