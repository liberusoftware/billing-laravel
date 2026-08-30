<?php

declare(strict_types=1);

namespace Liberu\Billing\Hosting\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Hosting\Models\HostingServer;

final class CreateHostingServer
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): HostingServer
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $panel = strtolower(trim((string) ($attributes['control_panel'] ?? '')));
        $hostname = trim((string) ($attributes['hostname'] ?? ''));
        if ($teamId < 1 || $name === '' || $hostname === '' || ! in_array($panel, ['cpanel', 'directadmin', 'virtualmin', 'virtualmin-pro', 'plesk', 'liberu'], true)) {
            throw new InvalidArgumentException('A valid hosting server name, hostname, and control panel are required.');
        }

        return DB::transaction(fn (): HostingServer => HostingServer::query()->create([
            'team_id' => $teamId,
            'name' => $name,
            'hostname' => $hostname,
            'username' => $attributes['username'] ?? null,
            'ip_address' => $attributes['ip_address'] ?? null,
            'control_panel' => $panel,
            'api_url' => $attributes['api_url'] ?? null,
            'api_token' => $attributes['api_token'] ?? null,
            'is_active' => $attributes['is_active'] ?? true,
            'max_accounts' => $attributes['max_accounts'] ?? null,
            'active_accounts' => 0,
            'metadata' => $attributes['metadata'] ?? null,
        ]));
    }
}
