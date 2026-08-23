<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RadiusClient;
use App\Enums\IspServiceStatus;
use App\Models\IspService;
use App\Models\RadiusSession;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IspServiceManager
{
    public function activate(IspService $service, RadiusClient $radius): IspService
    {
        if ($service->status === IspServiceStatus::Terminated) {
            throw new InvalidArgumentException('A terminated ISP service cannot be activated.');
        }

        $radius->synchronizeUser($service);
        $service->update([
            'status' => IspServiceStatus::Active,
            'activated_at' => $service->activated_at ?? now(),
            'suspended_at' => null,
            'suspension_reason' => null,
            'radius_synced_at' => now(),
        ]);

        return $service->refresh();
    }

    public function synchronize(IspService $service, RadiusClient $radius): IspService
    {
        $radius->synchronizeUser($service);
        $service->update(['radius_synced_at' => now()]);

        return $service->refresh();
    }

    public function suspend(IspService $service, RadiusClient $radius, string $reason): IspService
    {
        if ($service->status === IspServiceStatus::Terminated) {
            throw new InvalidArgumentException('A terminated ISP service cannot be suspended.');
        }

        $radius->suspendUser($service);
        $radius->disconnectUser($service);
        $service->update([
            'status' => IspServiceStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
            'radius_synced_at' => now(),
        ]);

        return $service->refresh();
    }

    /**
     * Apply an idempotent RADIUS interim or stop accounting update.
     *
     * @param  array{
     *   accounting_session_id: string,
     *   started_at: string,
     *   ended_at?: string|null,
     *   input_bytes?: int,
     *   output_bytes?: int,
     *   session_seconds?: int,
     *   nas_identifier?: string|null,
     *   ip_address?: string|null
     * }  $accounting
     */
    public function recordAccounting(IspService $service, array $accounting, RadiusClient $radius): RadiusSession
    {
        return DB::transaction(function () use ($service, $accounting, $radius): RadiusSession {
            $lockedService = IspService::query()->lockForUpdate()->findOrFail($service->id);
            $session = RadiusSession::query()->firstOrNew([
                'isp_service_id' => $lockedService->id,
                'accounting_session_id' => $accounting['accounting_session_id'],
            ]);
            $previousBytes = $session->exists ? $session->total_bytes : 0;

            $session->fill($accounting);
            $session->isp_service_id = $lockedService->id;
            $session->save();

            $newBytes = $session->total_bytes;
            $usageDelta = max(0, $newBytes - $previousBytes);
            $lockedService->increment('current_period_usage_bytes', $usageDelta);
            $lockedService->refresh();

            if ($lockedService->monthly_data_limit_bytes !== null
                && $lockedService->current_period_usage_bytes >= $lockedService->monthly_data_limit_bytes
                && $lockedService->status === IspServiceStatus::Active) {
                $this->suspend($lockedService, $radius, 'Monthly data allowance exceeded.');
            }

            return $session->refresh();
        });
    }

    public function resetUsagePeriod(IspService $service): IspService
    {
        $service->update(['current_period_usage_bytes' => 0]);

        return $service->refresh();
    }
}
