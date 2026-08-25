<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Enums;

enum ReconciliationStatus: string
{
    case Matched = 'matched';
    case Mismatch = 'mismatch';
}
