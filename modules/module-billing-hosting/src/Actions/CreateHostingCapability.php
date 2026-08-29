<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Liberu\Billing\Hosting\Models\HostingCapability;

final class CreateHostingCapability
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): HostingCapability
    {
        $type = (string) ($attributes['type'] ?? '');
        $name = trim((string) ($attributes['name'] ?? ''));
        if ($teamId < 1 || ! in_array($type, ['plan', 'control_panel', 'ssl', 'resource', 'lifecycle'], true) || $name === '') {
            throw new InvalidArgumentException('Hosting capability type and name are invalid.');
        }

        $accountId = $attributes['hosting_account_id'] ?? null;
        if ($accountId !== null) {
            HostingAccount::query()->forTeam($teamId)->findOrFail((int) $accountId);
        }

        return DB::transaction(fn (): HostingCapability => HostingCapability::query()->create(['team_id' => $teamId, 'hosting_account_id' => $accountId, 'type' => $type, 'name' => $name, 'status' => 'pending', 'provider' => $attributes['provider'] ?? null, 'configuration' => $attributes['configuration'] ?? []]));
    }
}
