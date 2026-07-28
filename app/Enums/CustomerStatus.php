<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerStatus: string
{
    case Prospect = 'prospect';
    case PendingVerification = 'pending_verification';
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';
    case Blacklisted = 'blacklisted';
}
