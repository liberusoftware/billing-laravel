<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Models\CommunicationService;

final class TransitionCommunicationService
{
    public function handle(CommunicationService $service, string $status): CommunicationService
    {
        if (! in_array($status, ['pending', 'active', 'suspended', 'cancelled', 'failed'], true)) {
            throw new InvalidArgumentException('Communications service lifecycle status is invalid.');
        }

        return DB::transaction(function () use ($service, $status): CommunicationService {
            $service->update(['status' => $status]);

            return $service->refresh();
        });
    }
}
