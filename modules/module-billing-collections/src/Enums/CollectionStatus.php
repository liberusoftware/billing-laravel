<?php

declare(strict_types=1);

namespace Liberu\Billing\Collections\Enums;

enum CollectionStatus: string
{
    case Open = 'open';
    case Promised = 'promised';
    case Suspended = 'suspended';
    case WrittenOff = 'written_off';
    case Recovered = 'recovered';
    case Closed = 'closed';
}
