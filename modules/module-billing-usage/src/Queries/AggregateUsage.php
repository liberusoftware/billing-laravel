<?php

declare(strict_types=1);

namespace Liberu\Billing\Usage\Queries;

use Illuminate\Support\Facades\DB;

final class AggregateUsage
{
    public function execute(int $meterId, ?int $customerId = null): object
    {
        return DB::table('billing_usage_records')->where('meter_id', $meterId)->when($customerId !== null, fn ($query) => $query->where('customer_id', $customerId))->selectRaw('COALESCE(SUM(quantity), 0) as quantity, COALESCE(SUM(amount_minor), 0) as amount_minor')->first();
    }
}
