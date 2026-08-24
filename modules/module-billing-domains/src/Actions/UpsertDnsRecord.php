<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Domains\Models\DnsRecord;

final class UpsertDnsRecord
{
    /** @param array<string,mixed> $attributes */
    public function execute(int $teamId, array $attributes): DnsRecord
    {
        $type = strtoupper((string) ($attributes['type'] ?? ''));
        if ($teamId < 1 || ! in_array($type, ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'], true) || trim((string) ($attributes['host'] ?? '')) === '' || trim((string) ($attributes['value'] ?? '')) === '') {
            throw new InvalidArgumentException('DNS record details are invalid.');
        }

return DB::transaction(fn (): DnsRecord => DnsRecord::query()->updateOrCreate(['team_id' => $teamId, 'domain_id' => (int) $attributes['domain_id'], 'type' => $type, 'host' => $attributes['host']], ['value' => $attributes['value'], 'ttl' => max(60, (int) ($attributes['ttl'] ?? 3600))]));
    }
}
