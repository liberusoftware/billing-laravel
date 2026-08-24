<?php

declare(strict_types=1);

namespace Liberu\Billing\Invoicing\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Invoicing\Models\Invoice;

final class ListInvoices
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Invoice::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->latest()->paginate(min(max($perPage, 1), 100));
    }
}
