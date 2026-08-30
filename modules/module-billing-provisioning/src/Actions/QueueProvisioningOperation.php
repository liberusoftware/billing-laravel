<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Provisioning\Events\ProvisioningOperationQueued;
use Liberu\Billing\Provisioning\Models\ProvisionedService;
use Liberu\Billing\Provisioning\Models\ProvisioningOperation;

final class QueueProvisioningOperation
{
    /** @param array<string,mixed> $payload */
    public function execute(ProvisionedService $service, string $operation, array $payload = []): ProvisioningOperation
    {
        if (! in_array($operation, ['provision', 'deprovision', 'poll', 'reconcile', 'rollback'], true)) {
            throw new \InvalidArgumentException('Provisioning operation is invalid.');
        }

        return DB::transaction(function () use ($service, $operation, $payload): ProvisioningOperation {
            $queued = ProvisioningOperation::query()->create(['team_id' => $service->team_id, 'provisioned_service_id' => $service->id, 'operation' => $operation, 'status' => 'queued', 'payload' => $payload]);
            ProvisioningOperationQueued::dispatch($queued);

            return $queued;
        });
    }
}
