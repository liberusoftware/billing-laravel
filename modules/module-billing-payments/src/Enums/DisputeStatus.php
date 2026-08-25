<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case Won = 'won';
    case Lost = 'lost';
    case Accepted = 'accepted';
}
