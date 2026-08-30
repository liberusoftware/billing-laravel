<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Core\Events\BillingAccountUpdated;
use Liberu\Billing\Core\Models\BillingAccount;

final readonly class UpdateBillingAccount
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array{name?: string, currency?: string, settings?: array<string, mixed>} $attributes */
    public function execute(BillingAccount $account, array $attributes): BillingAccount
    {
        return $this->database->transaction(function () use ($account, $attributes): BillingAccount {
            $locked = BillingAccount::query()->lockForUpdate()->findOrFail($account->getKey());
            if (array_key_exists('name', $attributes)) {
                $attributes['name'] = trim((string) $attributes['name']);
            }
            if (array_key_exists('currency', $attributes)) {
                $attributes['currency'] = strtoupper((string) $attributes['currency']);
            }
            $locked->fill($attributes)->save();
            BillingAccountUpdated::dispatch($locked);

            return $locked->refresh();
        });
    }
}
