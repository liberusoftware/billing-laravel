<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Liberu\Billing\Pricing\Models\PricingPlan;

final class ListPricingPlans
{
    public function execute(?int $teamId, int $perPage = 25): LengthAwarePaginator
    {
        return PricingPlan::query()->when($teamId !== null, fn ($query) => $query->where(function ($query) use ($teamId): void {
            $query->whereNull('team_id')->orWhere('team_id', $teamId);
        }))->latest()->paginate(min(max($perPage, 1), 100));
    }
}
