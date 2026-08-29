<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Hosting\Models\HostingCapability;

final class TransitionHostingCapability
{
    public function handle(HostingCapability $capability, string $status): HostingCapability
    {
        if (! in_array($status, ['pending', 'active', 'suspended', 'cancelled', 'failed'], true)) {
            throw new InvalidArgumentException('Hosting lifecycle status is invalid.');
        }

        return DB::transaction(function () use ($capability, $status): HostingCapability {
            $locked = HostingCapability::query()->lockForUpdate()->findOrFail($capability->getKey());
            if ($locked->status === 'cancelled' && $status !== 'cancelled') {
                throw new InvalidArgumentException('Cancelled hosting capabilities cannot be reactivated.');
            }

            $locked->update(['status' => $status]);

            return $locked->refresh();
        });
    }
}
