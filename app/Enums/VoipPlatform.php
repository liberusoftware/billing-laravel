<?php

declare(strict_types=1);

namespace App\Enums;

enum VoipPlatform: string
{
    case Asterisk = 'asterisk';
    case FreePbx = 'freepbx';
    case FusionPbx = 'fusionpbx';
    case ThreeCx = '3cx';
}
