<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Domains\Models\DomainContact;

final class CreateDomainContact
{
    /** @param array<string,mixed> $attributes */
    public function execute(int $teamId, array $attributes): DomainContact
    {
        $handle = trim((string) ($attributes['handle'] ?? ''));
        if ($teamId < 1 || $handle === '' || trim((string) ($attributes['name'] ?? '')) === '' || ! filter_var($attributes['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Domain contact details are invalid.');
        }

return DB::transaction(fn (): DomainContact => DomainContact::query()->create(['team_id' => $teamId, 'handle' => strtoupper($handle), 'name' => trim($attributes['name']), 'email' => strtolower($attributes['email']), 'details' => $attributes['details'] ?? []]));
    }
}
