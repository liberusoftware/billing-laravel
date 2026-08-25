<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Domains\Models\Domain;

final class UpdateDomain
{
    /** @param array<string,mixed> $attributes */
    public function handle(Domain $domain, array $attributes): Domain
    {
        return DB::transaction(function () use ($domain, $attributes): Domain {
            $domain->fill(array_filter([
                'name' => array_key_exists('name', $attributes) ? $this->normalizeName((string) $attributes['name']) : null,
                'status' => $attributes['status'] ?? null,
                'registrar' => $attributes['registrar'] ?? null,
                'transfer_status' => $attributes['transfer_status'] ?? null,
                'expires_at' => $attributes['expires_at'] ?? null,
                'registered_at' => $attributes['registered_at'] ?? null,
                'metadata' => $attributes['metadata'] ?? null,
            ], static fn (mixed $value): bool => $value !== null));
            $domain->save();

            return $domain->refresh();
        });
    }

    private function normalizeName(string $name): string
    {
        return strtolower(rtrim(trim($name), '.'));
    }
}
