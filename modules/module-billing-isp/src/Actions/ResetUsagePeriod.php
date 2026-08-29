<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Isp\Models\AccessService;

final readonly class ResetUsagePeriod
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(AccessService $service): AccessService
    {
        return $this->database->transaction(function () use ($service): AccessService {
            $locked = AccessService::query()->lockForUpdate()->findOrFail($service->getKey());
            $locked->update(['current_period_usage_bytes' => 0]);

            return $locked->refresh();
        });
    }
}
