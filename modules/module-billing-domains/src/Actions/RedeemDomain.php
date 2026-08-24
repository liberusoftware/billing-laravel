<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Domains\Models\Domain;

final class RedeemDomain
{
    public function execute(Domain $domain): Domain
    {
        return DB::transaction(function () use ($domain): Domain {
            $metadata = $domain->metadata ?? [];
            $metadata['redemption_requested_at'] = now()->toIso8601String();
            $domain->update(['status' => 'redemption_pending', 'metadata' => $metadata]);

            return $domain->refresh();
        });
    }
}
