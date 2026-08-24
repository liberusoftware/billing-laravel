<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Enums;

enum PricingPlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
