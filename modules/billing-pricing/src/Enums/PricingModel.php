<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Enums;

enum PricingModel: string
{
    case Recurring = 'recurring';
    case OneTime = 'one_time';
    case Usage = 'usage';
    case Tiered = 'tiered';
}
