<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Pricing\Models\PricingPlan;

final class CalculateUsageBasedPrice
{
    public function __construct(private readonly CalculatePricingPlanAmount $calculate) {}

    /**
     * Aggregate modular usage and price it with the plan's configured rules.
     *
     * @return array{quantity: float, amount_minor: int, currency: string, meter_id: int, start: string, end: string}
     */
    public function execute(
        PricingPlan $plan,
        int $meterId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $customerId = null,
    ): array {
        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('Usage period end must be on or after its start.');
        }

        if (! in_array($plan->pricing_model->value, ['usage', 'tiered'], true)) {
            throw new InvalidArgumentException('Usage pricing requires a usage or tiered pricing plan.');
        }

        $usage = DB::table('billing_usage_records')
            ->where('meter_id', $meterId)
            ->where('team_id', $plan->team_id)
            ->when($customerId !== null, fn ($query) => $query->where('customer_id', $customerId))
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(quantity), 0) as quantity')
            ->value('quantity');
        $quantity = (float) $usage;

        return [
            'quantity' => $quantity,
            'amount_minor' => $this->calculate->execute($plan, ['quantity' => $quantity]),
            'currency' => (string) $plan->currency,
            'meter_id' => $meterId,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
        ];
    }
}
