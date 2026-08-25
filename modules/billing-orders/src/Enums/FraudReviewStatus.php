<?php

declare(strict_types=1);

namespace Liberu\Billing\Orders\Enums;

enum FraudReviewStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Cleared = 'cleared';
    case Blocked = 'blocked';
}
