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
        $status = (string) ($attributes['status'] ?? 'active');
        if ($teamId < 1 || $name === '' || ! in_array($status, ['pending', 'active', 'suspended', 'cancelled', 'failed'], true)) {
            throw new InvalidArgumentException('A team and name are required.');
        }

        return DB::transaction(fn (): HostingAccount => HostingAccount::query()->create([
            'team_id' => $teamId, 'name' => $name, 'status' => $status, 'metadata' => $attributes['metadata'] ?? null,
        ]));
    }
}
