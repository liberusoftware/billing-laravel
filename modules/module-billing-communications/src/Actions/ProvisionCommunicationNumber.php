<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Models\CommunicationNumber;
use Liberu\Billing\Communications\Models\CommunicationService;

final class ProvisionCommunicationNumber
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): CommunicationNumber
    {
        $number = trim((string) ($attributes['number'] ?? ''));
        if ($teamId < 1 || $number === '') {
            throw new InvalidArgumentException('A team and number are required.');
        }

        $serviceId = $attributes['service_id'] ?? null;
        if ($serviceId !== null) {
            CommunicationService::query()->forTeam($teamId)->findOrFail((int) $serviceId);
        }

        return DB::transaction(fn (): CommunicationNumber => CommunicationNumber::query()->create(['team_id' => $teamId, 'service_id' => $serviceId, 'number' => $number, 'type' => $attributes['type'] ?? 'phone', 'status' => 'active', 'metadata' => $attributes['metadata'] ?? []]));
    }
}
