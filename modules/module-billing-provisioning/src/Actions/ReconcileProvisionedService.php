<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Provisioning\Models\ProvisionedService;

final readonly class ReconcileProvisionedService
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(ProvisionedService $service): ProvisionedService
    {
        return $this->database->transaction(function () use ($service): ProvisionedService {
            $service->update(['last_reconciled_at' => now()]);

            return $service->refresh();
        });
    }
}
