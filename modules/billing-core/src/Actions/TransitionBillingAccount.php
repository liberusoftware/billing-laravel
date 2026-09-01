<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;
use Liberu\Billing\Core\Enums\BillingAccountStatus;
use Liberu\Billing\Core\Events\BillingAccountStatusChanged;
use Liberu\Billing\Core\Models\BillingAccount;

final readonly class TransitionBillingAccount
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(BillingAccount $account, BillingAccountStatus $status): BillingAccount
    {
        if ($account->status === BillingAccountStatus::Closed && $status !== BillingAccountStatus::Closed) {
            throw new InvalidArgumentException('A closed billing account cannot be reopened.');
        }

        return $this->database->transaction(function () use ($account, $status): BillingAccount {
            $locked = BillingAccount::query()->lockForUpdate()->findOrFail($account->getKey());
            if ($locked->status === BillingAccountStatus::Closed && $status !== BillingAccountStatus::Closed) {
                throw new InvalidArgumentException('A closed billing account cannot be reopened.');
            }

            $from = $locked->status;
            $locked->update(['status' => $status]);
            BillingAccountStatusChanged::dispatch($locked, $from->value, $status->value);

            return $locked->refresh();
        });
    }
}
