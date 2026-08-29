<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Isp\Models\AccessService;
use Liberu\Billing\Isp\Services\NetworkAdapterRegistry;

final readonly class SynchronizeAccessService
{
    public function __construct(private DatabaseManager $database, private NetworkAdapterRegistry $adapters) {}

    public function execute(AccessService $service, string $adapter): AccessService
    {
        $adapter = $this->adapters->resolve($adapter);
        $result = $adapter->install(['service_id' => $service->getKey(), 'team_id' => $service->team_id, 'name' => $service->name, 'metadata' => $service->metadata ?? []]);

        return $this->database->transaction(function () use ($service, $result): AccessService {
            $locked = AccessService::query()->lockForUpdate()->findOrFail($service->getKey());
            $locked->update(['radius_synced_at' => now(), 'metadata' => array_merge($locked->metadata ?? [], ['network_adapter' => $result['adapter'] ?? null, 'external_reference' => $result['reference'] ?? null])]);

            return $locked->refresh();
        });
    }
}
