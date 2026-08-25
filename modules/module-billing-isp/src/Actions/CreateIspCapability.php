<?php

declare(strict_types=1);

namespace Liberu\Billing\Isp\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Isp\Models\IspCapability;

final class CreateIspCapability
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): IspCapability
    {
        $type = (string) ($attributes['type'] ?? '');
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($teamId < 1 || ! in_array($type, ['coverage', 'installation', 'radius', 'usage', 'equipment', 'network_adapter'], true) || $name === '') {
            throw new InvalidArgumentException('ISP capability details are invalid.');
        }

        return DB::transaction(fn (): IspCapability => IspCapability::query()->create(['team_id' => $teamId, 'type' => $type, 'name' => $name, 'status' => 'pending', 'identifier' => $attributes['identifier'] ?? null, 'configuration' => $attributes['configuration'] ?? []]));
    }
}
