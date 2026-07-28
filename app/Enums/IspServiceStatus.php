<?php

declare(strict_types=1);

namespace App\Enums;

enum IspServiceStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
}
