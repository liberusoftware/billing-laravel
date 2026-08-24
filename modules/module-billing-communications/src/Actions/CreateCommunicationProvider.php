<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Models\CommunicationProvider;

final class CreateCommunicationProvider
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): CommunicationProvider
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $driver = trim((string) ($attributes['driver'] ?? ''));
        if ($teamId < 1 || $name === '' || $driver === '') {
            throw new InvalidArgumentException('A team, provider name, and driver are required.');
        }

        return DB::transaction(fn (): CommunicationProvider => CommunicationProvider::query()->create([
            'team_id' => $teamId,
            'name' => $name,
            'driver' => $driver,
            'status' => $attributes['status'] ?? 'active',
            'configuration' => $attributes['configuration'] ?? [],
        ]));
    }
}
