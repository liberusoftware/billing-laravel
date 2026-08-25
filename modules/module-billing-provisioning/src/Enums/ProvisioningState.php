<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Enums;

enum ProvisioningState: string
{
    case Pending = 'pending';
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Suspended = 'suspended';
    case Failed = 'failed';
    case Deprovisioning = 'deprovisioning';
    case Deprovisioned = 'deprovisioned';
}
