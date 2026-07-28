<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\IspService;

interface RadiusClient
{
    public function synchronizeUser(IspService $service): void;

    public function suspendUser(IspService $service): void;

    public function disconnectUser(IspService $service): void;
}
