<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\VoipAccount;

interface VoipPlatformClient
{
    public function provisionAccount(VoipAccount $account): void;

    public function synchronizeAccount(VoipAccount $account): void;

    public function suspendAccount(VoipAccount $account): void;
}
