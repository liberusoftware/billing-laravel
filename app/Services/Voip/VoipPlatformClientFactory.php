<?php

declare(strict_types=1);

namespace App\Services\Voip;

use App\Contracts\VoipPlatformClient;
use App\Enums\VoipPlatform;

class VoipPlatformClientFactory
{
    public function make(VoipPlatform $platform): VoipPlatformClient
    {
        return new HttpVoipPlatformClient($platform);
    }
}
