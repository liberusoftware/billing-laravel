<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Domains\Events\DomainCreated;
use Liberu\Billing\Domains\Models\Domain;

final class CreateDomain
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): Domain
    {
        $name = strtolower(rtrim(trim((string) ($attributes['name'] ?? '')), '.'));
        if ($teamId < 1 || $name === '') {
            throw new InvalidArgumentException('A team and name are required.');
        }

        return DB::transaction(function () use ($teamId, $name, $attributes): Domain {
            $domain = Domain::query()->create([
                'team_id' => $teamId,
                'name' => $name,
                'status' => $attributes['status'] ?? 'active',
                'registrar' => $attributes['registrar'] ?? null,
                'transfer_status' => $attributes['transfer_status'] ?? null,
                'expires_at' => $attributes['expires_at'] ?? null,
                'registered_at' => $attributes['registered_at'] ?? null,
                'metadata' => $attributes['metadata'] ?? null,
            ]);
            DomainCreated::dispatch($domain);

            return $domain;
        });
    }
}
