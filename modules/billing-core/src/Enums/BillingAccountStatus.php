<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Enums;

enum BillingAccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';
}
