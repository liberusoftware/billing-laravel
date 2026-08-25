<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Models\CommunicationUsageImport;

final class ImportCommunicationUsage
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): CommunicationUsageImport
    {
        $provider = trim((string) ($attributes['provider'] ?? ''));
        $rows = (int) ($attributes['rows'] ?? 0);
        if ($teamId < 1 || $provider === '' || $rows < 1) {
            throw new InvalidArgumentException('A team, provider, and positive row count are required.');
        }

        return DB::transaction(fn (): CommunicationUsageImport => CommunicationUsageImport::query()->create(['team_id' => $teamId, 'provider' => $provider, 'status' => 'completed', 'rows' => $rows, 'total_amount_minor' => max(0, (int) ($attributes['total_amount_minor'] ?? 0)), 'currency' => strtoupper((string) ($attributes['currency'] ?? 'USD')), 'metadata' => $attributes['metadata'] ?? []]));
    }
}
