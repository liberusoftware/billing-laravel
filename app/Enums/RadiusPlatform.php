<?php

declare(strict_types=1);

namespace App\Enums;

enum RadiusPlatform: string
{
    case FreeRadius = 'freeradius';
    case MikroTik = 'mikrotik';
    case Cisco = 'cisco';
}
