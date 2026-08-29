<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Isp\Models\AccessService;
use Liberu\Billing\Isp\Models\RadiusSession;

final readonly class RecordRadiusAccounting
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array{accounting_session_id:string,started_at:string,ended_at?:string|null,input_bytes?:int,output_bytes?:int,session_seconds?:int,nas_identifier?:string|null,ip_address?:string|null} $accounting */
    public function execute(AccessService $service, array $accounting): RadiusSession
    {
        $sessionId = trim($accounting['accounting_session_id'] ?? '');
        if ($sessionId === '' || ! isset($accounting['started_at'])) {
            throw new \InvalidArgumentException('RADIUS accounting session details are invalid.');
        }

        return $this->database->transaction(function () use ($service, $accounting, $sessionId): RadiusSession {
            $locked = AccessService::query()->lockForUpdate()->findOrFail($service->getKey());
            $session = RadiusSession::query()->firstOrNew(['access_service_id' => $locked->getKey(), 'accounting_session_id' => $sessionId]);
            $previous = (int) ($session->total_bytes ?? 0);
            $input = max(0, (int) ($accounting['input_bytes'] ?? 0));
            $output = max(0, (int) ($accounting['output_bytes'] ?? 0));
            $total = $input + $output;
            $session->fill(['team_id' => $locked->team_id, 'started_at' => $accounting['started_at'], 'ended_at' => $accounting['ended_at'] ?? null, 'input_bytes' => $input, 'output_bytes' => $output, 'total_bytes' => $total, 'session_seconds' => $accounting['session_seconds'] ?? null, 'nas_identifier' => $accounting['nas_identifier'] ?? null, 'ip_address' => $accounting['ip_address'] ?? null]);
            $session->save();
            $locked->increment('current_period_usage_bytes', max(0, $total - $previous));
            $locked->refresh();
            if ($locked->monthly_data_limit_bytes !== null && $locked->current_period_usage_bytes >= $locked->monthly_data_limit_bytes && $locked->status === 'active') {
                $locked->update(['status' => 'suspended', 'suspended_at' => now(), 'suspension_reason' => 'Monthly data allowance exceeded.']);
            }

            return $session->refresh();
        });
    }
}
