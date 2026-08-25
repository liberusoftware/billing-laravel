<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Invoicing\Models\Invoice;

final class ListInvoices
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Invoice::query()->where('team_id', $teamId ?? -1)->latest()->paginate(min(max($perPage, 1), 100));
    }
}
