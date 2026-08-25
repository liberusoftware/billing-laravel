<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Reporting\Models\MetricSnapshot;

final class CreateMetricSnapshot
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): MetricSnapshot
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($teamId < 1 || $name === '') {
            throw new InvalidArgumentException('A team and name are required.');
        }

        return DB::transaction(fn (): MetricSnapshot => MetricSnapshot::query()->create([
            'team_id' => $teamId, 'name' => $name, 'status' => $attributes['status'] ?? 'active', 'metadata' => $attributes['metadata'] ?? null,
        ]));
    }
}
