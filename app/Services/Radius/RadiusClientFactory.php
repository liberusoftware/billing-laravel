<?php

declare(strict_types=1);

namespace App\Services\Radius;

use App\Contracts\RadiusClient;
use App\Enums\RadiusPlatform;

class RadiusClientFactory
{
    public function make(RadiusPlatform $platform): RadiusClient
    {
        return new HttpRadiusClient($platform);
    }
}
