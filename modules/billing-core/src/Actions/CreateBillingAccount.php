<?php

declare(strict_types=1);

namespace Liberu\Billing\Core\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Core\Enums\BillingAccountStatus;
use Liberu\Billing\Core\Events\BillingAccountCreated;
use Liberu\Billing\Core\Models\BillingAccount;

final readonly class CreateBillingAccount
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array{team_id?: int|null, name: string, currency: string, settings?: array<string, mixed>} $attributes */
    public function execute(array $attributes): BillingAccount
    {
        return $this->database->transaction(function () use ($attributes): BillingAccount {
            $account = BillingAccount::query()->create([
                'team_id' => $attributes['team_id'] ?? null,
                'name' => trim($attributes['name']),
                'currency' => strtoupper($attributes['currency']),
                'status' => BillingAccountStatus::Active,
                'settings' => $attributes['settings'] ?? [],
            ]);

            BillingAccountCreated::dispatch($account);

            return $account;
        });
    }
}
