<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;

final readonly class PerformHostingOperation
{
    public function __construct(private HostingDriverRegistry $drivers) {}

    public function handle(HostingAccount $account, string $operation): HostingAccount
    {
        $operation = strtolower(trim($operation));
        if (! in_array($operation, ['provision', 'suspend', 'terminate'], true)) {
            throw new \InvalidArgumentException('Hosting operation is invalid.');
        }

        return DB::transaction(function () use ($account, $operation): HostingAccount {
            $locked = HostingAccount::query()->lockForUpdate()->findOrFail($account->getKey());
            $allowed = match ($operation) {
                'provision' => ['pending'],
                'suspend' => ['active'],
                'terminate' => ['active', 'suspended'],
            };
            if (! in_array($locked->status, $allowed, true)) {
                throw new \LogicException("Hosting account cannot be {$operation}d from its current state.");
            }

            $metadata = is_array($locked->metadata) ? $locked->metadata : [];
            $driverKey = $metadata['driver'] ?? $metadata['hosting_driver'] ?? null;
            if (! is_string($driverKey) || trim($driverKey) === '') {
                throw new \InvalidArgumentException('A hosting driver is required.');
            }
            $result = $this->drivers->resolve($driverKey)->{$operation}(['account_id' => $locked->getKey(), 'team_id' => $locked->team_id, 'name' => $locked->name, 'status' => $locked->status, 'metadata' => $metadata]);
            $metadata['last_operation'] = ['operation' => $operation, 'result' => $result, 'completed_at' => now()->toIso8601String()];
            $locked->update(['status' => $operation === 'provision' ? 'active' : ($operation === 'suspend' ? 'suspended' : 'cancelled'), 'metadata' => $metadata]);

            return $locked->refresh();
        });
    }
}
