<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Captured = 'captured';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Disputed = 'disputed';
}
