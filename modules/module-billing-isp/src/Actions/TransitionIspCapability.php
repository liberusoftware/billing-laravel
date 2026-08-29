<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Isp\Models\IspCapability;

final class TransitionIspCapability
{
    public function handle(IspCapability $capability, string $status): IspCapability
    {
        if (! in_array($status, ['pending', 'active', 'suspended', 'cancelled', 'failed'], true)) {
            throw new InvalidArgumentException('ISP capability status is invalid.');
        }
        if ($capability->status === 'cancelled' && $status !== 'cancelled') {
            throw new \LogicException('A cancelled ISP capability cannot be reactivated.');
        }

        return DB::transaction(function () use ($capability, $status): IspCapability {
            $locked = IspCapability::query()->lockForUpdate()->findOrFail($capability->getKey());
            if ($locked->status === 'cancelled' && $status !== 'cancelled') {
                throw new \LogicException('A cancelled ISP capability cannot be reactivated.');
            }
            $locked->update(['status' => $status]);

            return $locked->refresh();
        });
    }
}
