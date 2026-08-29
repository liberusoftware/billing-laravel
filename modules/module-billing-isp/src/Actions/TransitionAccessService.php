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
        if ($service->status === 'cancelled' && $status !== 'cancelled') {
            throw new \LogicException('A cancelled ISP access service cannot be reactivated.');
        }

        return DB::transaction(function () use ($service, $status): AccessService {
            $locked = AccessService::query()->lockForUpdate()->findOrFail($service->getKey());
            if ($locked->status === 'cancelled' && $status !== 'cancelled') {
                throw new \LogicException('A cancelled ISP access service cannot be reactivated.');
            }
            $locked->update(['status' => $status]);

            return $locked->refresh();
        });
    }
}
