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

        return DB::transaction(function () use ($capability, $status): IspCapability {
            $capability->update(['status' => $status]);

            return $capability->refresh();
        });
    }
}
