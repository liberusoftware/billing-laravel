<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Hosting\Models\HostingAccount;

final class CreateHostingAccount
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): HostingAccount
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($teamId < 1 || $name === '') {
            throw new InvalidArgumentException('A team and name are required.');
        }

        return DB::transaction(fn (): HostingAccount => HostingAccount::query()->create([
            'team_id' => $teamId, 'name' => $name, 'status' => $attributes['status'] ?? 'active', 'metadata' => $attributes['metadata'] ?? null,
        ]));
    }
}
