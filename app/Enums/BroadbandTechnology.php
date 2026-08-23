<?php

declare(strict_types=1);

namespace App\Enums;

enum BroadbandTechnology: string
{
    case Ftth = 'ftth';
    case Gpon = 'gpon';
    case Dsl = 'dsl';
    case Wireless = 'wireless';
    case Fibre = 'fibre';
}
