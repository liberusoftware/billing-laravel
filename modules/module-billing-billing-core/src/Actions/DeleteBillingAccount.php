<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Core\Events\BillingAccountDeleted;
use Liberu\Billing\Core\Models\BillingAccount;

final readonly class DeleteBillingAccount
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(BillingAccount $account): void
    {
        $accountId = $account->getKey();
        $teamId = $account->team_id === null ? null : (int) $account->team_id;

        $this->database->transaction(function () use ($account, $accountId, $teamId): void {
            $locked = BillingAccount::query()->lockForUpdate()->findOrFail($account->getKey());
            $locked->delete();
            BillingAccountDeleted::dispatch($accountId, $teamId);
        });
    }
}
