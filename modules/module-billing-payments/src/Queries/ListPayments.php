<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Payments\Models\Payment;

final class ListPayments
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return Payment::query()->when($teamId !== null, fn ($query) => $query->where('team_id', $teamId))->latest()->paginate(min(max($perPage, 1), 100));
    }
}
