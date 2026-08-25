<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Isp\Models\AccessService;

final class TransitionAccessService
{
    public function handle(AccessService $service, string $status): AccessService
    {
        if (! in_array($status, ['pending', 'active', 'suspended', 'cancelled', 'failed'], true)) {
            throw new InvalidArgumentException('ISP access-service lifecycle status is invalid.');
        }

        return DB::transaction(function () use ($service, $status): AccessService {
            $service->update(['status' => $status]);

            return $service->refresh();
        });
    }
}
