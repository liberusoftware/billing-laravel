<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Contracts;

use Liberu\Billing\Provisioning\Models\ProvisionedService;

interface ProvisioningDriver
{
    public function provision(ProvisionedService $service): string;

    public function deprovision(ProvisionedService $service): void;

    /** @return array{state: string, external_id?: string|null, error?: string|null} */
    public function poll(ProvisionedService $service): array;
}
