<?php

declare(strict_types=1);

namespace App\Enums;

enum VoipAccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
}
