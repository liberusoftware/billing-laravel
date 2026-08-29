<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Hosting\Models\HostingAccount;

final class TransitionHostingAccount
{
    public function handle(HostingAccount $account, string $status): HostingAccount
    {
        if (! in_array($status, ['pending', 'active', 'suspended', 'cancelled', 'failed'], true)) {
            throw new InvalidArgumentException('Hosting account lifecycle status is invalid.');
        }

        return DB::transaction(function () use ($account, $status): HostingAccount {
            $lockedAccount = HostingAccount::query()->lockForUpdate()->findOrFail($account->getKey());

            if ($lockedAccount->status === 'cancelled' && $status !== 'cancelled') {
                throw new InvalidArgumentException('Cancelled hosting accounts cannot be reactivated.');
            }

            $lockedAccount->update(['status' => $status]);

            return $lockedAccount->refresh();
        });
    }
}
