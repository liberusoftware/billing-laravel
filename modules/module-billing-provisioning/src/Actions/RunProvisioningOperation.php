<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Provisioning\Enums\ProvisioningState;
use Liberu\Billing\Provisioning\Events\ProvisioningOperationCompleted;
use Liberu\Billing\Provisioning\Events\ProvisioningOperationFailed;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;
use Liberu\Billing\Provisioning\Services\ProvisioningDriverRegistry;

final readonly class RunProvisioningOperation
{
    public function __construct(private DatabaseManager $database, private ProvisioningDriverRegistry $drivers) {}

    public function execute(ProvisioningOperation $operation): ProvisioningOperation
    {
        $claimed = $this->database->transaction(function () use ($operation): ?ProvisioningOperation {
            $locked = ProvisioningOperation::query()->lockForUpdate()->findOrFail($operation->getKey());
            if ($locked->status === 'completed') {
                return null;
            }
            if ($locked->status === 'running') {
                throw new \LogicException('Provisioning operation is already running.');
            }

            $locked->update(['status' => 'running', 'attempts' => ((int) $locked->attempts) + 1]);

            return $locked->load('service');
        });

        if ($claimed === null) {
            return $operation->refresh();
        }

        $operation = $claimed;
        $service = $operation->service;
        $driver = $this->drivers->resolve((string) $service->provider);

        try {
            $result = match ($operation->operation) {
                'provision' => ['external_id' => $driver->provision($service)],
                'deprovision', 'rollback' => (function () use ($driver, $service): array {
                    $driver->deprovision($service);

                    return [];
                })(),
                'poll', 'reconcile' => $driver->poll($service),
                default => throw new \InvalidArgumentException('Unsupported provisioning operation.'),
            };
            $state = $result['state'] ?? ($operation->operation === 'deprovision' || $operation->operation === 'rollback' ? ProvisioningState::Deprovisioned->value : ProvisioningState::Active->value);
            $operation = $this->database->transaction(function () use ($operation, $service, $result, $state): ProvisioningOperation {
                $service->forceFill(['external_id' => $result['external_id'] ?? $service->external_id, 'state' => $state, 'last_error' => $result['error'] ?? null, 'last_reconciled_at' => now()])->save();
                $operation->update(['status' => 'completed', 'next_poll_at' => null, 'error' => null]);
                $updated = $operation->refresh();
                ProvisioningOperationCompleted::dispatch($updated);

                return $updated;
            });
        } catch (\Throwable $exception) {
            $operation = $this->database->transaction(function () use ($operation, $exception): ProvisioningOperation {
                $operation->update(['status' => 'failed', 'error' => $exception->getMessage(), 'next_poll_at' => now()->addMinutes(5)]);
                $updated = $operation->refresh();
                ProvisioningOperationFailed::dispatch($updated);

                return $updated;
            });
            throw $exception;
        }

        return $operation->refresh();
    }
}
