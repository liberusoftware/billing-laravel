<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Enums;

enum PaymentMethodStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
