<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Models\EppOperation;

final class RecordEppOperation
{
    /** @param array<string, mixed> $payload */
    public function execute(Domain $domain, string $operation, string $status, array $payload = [], ?string $eppCode = null): EppOperation
    {
        return EppOperation::query()->create([
            'team_id' => $domain->team_id,
            'domain_id' => $domain->getKey(),
            'operation' => $operation,
            'status' => $status,
            'epp_code' => $eppCode,
            'payload' => $payload,
        ]);
    }
}
