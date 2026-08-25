<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Provisioning\Enums\ProvisioningState;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;
use Liberu\Billing\Provisioning\Services\ProvisioningDriverRegistry;

final readonly class RunProvisioningOperation
{
    public function __construct(private DatabaseManager $database, private ProvisioningDriverRegistry $drivers) {}

    public function execute(ProvisioningOperation $operation): ProvisioningOperation
    {
        if ($operation->status === 'completed') {
            return $operation;
        }

        $service = $operation->service;
        $driver = $this->drivers->resolve((string) $service->provider);

        $operation->update(['status' => 'running', 'attempts' => ((int) $operation->attempts) + 1]);

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
            $this->database->transaction(function () use ($operation, $service, $result, $state): void {
                $service->forceFill(['external_id' => $result['external_id'] ?? $service->external_id, 'state' => $state, 'last_error' => $result['error'] ?? null, 'last_reconciled_at' => now()])->save();
                $operation->update(['status' => 'completed', 'next_poll_at' => null, 'error' => null]);
            });
        } catch (\Throwable $exception) {
            $operation->update(['status' => 'failed', 'error' => $exception->getMessage(), 'next_poll_at' => now()->addMinutes(5)]);
            throw $exception;
        }

        return $operation->refresh();
    }
}
