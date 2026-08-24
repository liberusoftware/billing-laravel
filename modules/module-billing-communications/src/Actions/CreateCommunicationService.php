<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Models\CommunicationService;

final class CreateCommunicationService
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): CommunicationService
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($teamId < 1 || $name === '') {
            throw new InvalidArgumentException('A team and name are required.');
        }

        return DB::transaction(fn (): CommunicationService => CommunicationService::query()->create([
            'team_id' => $teamId, 'name' => $name, 'status' => $attributes['status'] ?? 'active', 'metadata' => $attributes['metadata'] ?? null,
        ]));
    }
}
