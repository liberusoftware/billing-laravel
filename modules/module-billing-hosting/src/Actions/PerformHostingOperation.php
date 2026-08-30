<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Hosting\Contracts\ControlPanelDriver;
use Liberu\Billing\Hosting\Events\HostingOperationPerformed;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;

final readonly class PerformHostingOperation
{
    public function __construct(private HostingDriverRegistry $drivers) {}

    public function handle(HostingAccount $account, string $operation): HostingAccount
    {
        $operation = strtolower(trim($operation));
        if (! in_array($operation, ['provision', 'suspend', 'unsuspend', 'change_package', 'terminate', 'add_addon', 'remove_addon'], true)) {
            throw new \InvalidArgumentException('Hosting operation is invalid.');
        }

        return DB::transaction(function () use ($account, $operation): HostingAccount {
            $locked = HostingAccount::query()->lockForUpdate()->findOrFail($account->getKey());
            $allowed = match ($operation) {
                'provision' => ['pending'],
                'suspend' => ['active'],
                'unsuspend' => ['suspended'],
                'change_package', 'add_addon', 'remove_addon' => ['active'],
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
            $driver = $this->drivers->resolve($driverKey);
            $payload = ['account_id' => $locked->getKey(), 'team_id' => $locked->team_id, 'name' => $locked->name, 'status' => $locked->status, 'metadata' => $metadata];
            if ($driver instanceof ControlPanelDriver) {
                $payload = array_merge($payload, $metadata['account'] ?? [], ['server' => $metadata['server'] ?? []]);
            }
            $method = match ($operation) {
                'change_package' => 'changePackage',
                'add_addon' => 'addAddon',
                'remove_addon' => 'removeAddon',
                default => $operation,
            };
            if (! method_exists($driver, $method)) {
                throw new \InvalidArgumentException("Hosting driver [{$driverKey}] does not support [{$operation}].");
            }
            $result = $driver->{$method}($payload);
            $metadata['last_operation'] = ['operation' => $operation, 'result' => $result, 'completed_at' => now()->toIso8601String()];
            $status = match ($operation) {
                'provision', 'unsuspend', 'change_package', 'add_addon', 'remove_addon' => 'active',
                'suspend' => 'suspended',
                default => 'cancelled',
            };
            $locked->update(['status' => $status, 'metadata' => $metadata]);
            $updated = $locked->refresh();
            HostingOperationPerformed::dispatch($updated, $operation);

            return $updated;
        });
    }
}
