<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Domains\Events\DnsRecordUpserted;
use Liberu\Billing\Domains\Models\DnsRecord;
use Liberu\Billing\Domains\Models\Domain;

final class UpsertDnsRecord
{
    /** @param array<string,mixed> $attributes */
    public function execute(int $teamId, array $attributes): DnsRecord
    {
        $type = strtoupper((string) ($attributes['type'] ?? ''));
        $domainId = (int) ($attributes['domain_id'] ?? 0);
        if ($teamId < 1 || $domainId < 1 || ! in_array($type, ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'], true) || trim((string) ($attributes['host'] ?? '')) === '' || trim((string) ($attributes['value'] ?? '')) === '' || ! Domain::query()->whereKey($domainId)->where('team_id', $teamId)->exists()) {
            throw new InvalidArgumentException('DNS record details are invalid.');
        }

        return DB::transaction(function () use ($teamId, $domainId, $type, $attributes): DnsRecord {
            $record = DnsRecord::query()->updateOrCreate(['team_id' => $teamId, 'domain_id' => $domainId, 'type' => $type, 'host' => trim((string) $attributes['host'])], ['value' => trim((string) $attributes['value']), 'ttl' => max(60, (int) ($attributes['ttl'] ?? 3600))]);
            DnsRecordUpserted::dispatch($record);

            return $record;
        });
    }
}
