<?php

declare(strict_types=1);

namespace Liberu\Billing\Reporting\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Liberu\Billing\Reporting\Enums\ReportingMetricType;

final readonly class CalculateReportingMetric
{
    public function __construct(private DatabaseManager $database) {}

    /** @return array{metric:string,value:float,currency:?string,period_start:string,period_end:string,dimensions:array<string,mixed>} */
    public function execute(int $teamId, string $metric, CarbonImmutable $periodStart, CarbonImmutable $periodEnd, ?string $currency = null): array
    {
        if ($teamId < 1 || ReportingMetricType::tryFrom($metric) === null || $periodEnd->lt($periodStart)) {
            throw new \InvalidArgumentException('Reporting metric calculation details are invalid.');
        }

        $currency = $currency === null ? null : strtoupper($currency);
        if ($currency !== null && ! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Reporting metric currency is invalid.');
        }

        $value = match ($metric) {
            'mrr' => $this->monthlyRecurringRevenue($teamId, $periodEnd, $currency),
            'arr' => $this->monthlyRecurringRevenue($teamId, $periodEnd, $currency) * 12,
            'churn' => $this->churn($teamId, $periodStart, $periodEnd),
            'aging' => $this->invoiceSum($teamId, $periodEnd, 'aging', $currency),
            'revenue' => $this->invoiceSum($teamId, $periodEnd, 'revenue', $currency, $periodStart),
            'tax' => $this->invoiceSum($teamId, $periodEnd, 'tax', $currency, $periodStart),
            'usage' => $this->usageSum($teamId, $periodStart, $periodEnd, $currency),
            'provisioning' => $this->countRows('billing_provisioned_services', $teamId, $periodEnd, ['state' => 'active']),
            'collection' => $this->collectionSum($teamId, $periodEnd, $currency),
            'provider' => $this->providerSum($teamId, $periodStart, $periodEnd, $currency),
        };

        return ['metric' => $metric, 'value' => (float) $value, 'currency' => $currency, 'period_start' => $periodStart->toDateString(), 'period_end' => $periodEnd->toDateString(), 'dimensions' => []];
    }

    private function monthlyRecurringRevenue(int $teamId, CarbonImmutable $at, ?string $currency): float
    {
        if (! Schema::hasTable('billing_subscriptions') || ! Schema::hasTable('billing_pricing_plans')) {
            return 0.0;
        }

        return (float) $this->database->table('billing_subscriptions as subscriptions')
            ->join('billing_pricing_plans as plans', 'plans.id', '=', 'subscriptions.pricing_plan_id')
            ->where('subscriptions.team_id', $teamId)
            ->whereIn('subscriptions.status', ['active', 'trialing'])
            ->where('subscriptions.starts_at', '<=', $at)
            ->where(function ($query) use ($at): void {
                $query->whereNull('subscriptions.cancelled_at')->orWhere('subscriptions.cancelled_at', '>', $at);
            })
            ->when($currency !== null, fn ($query) => $query->where('plans.currency', $currency))
            ->get(['plans.unit_amount_minor', 'plans.billing_interval'])
            ->sum(function (object $plan): float {
                $amount = (float) $plan->unit_amount_minor;

                return match (strtolower((string) $plan->billing_interval)) {
                    'year', 'yearly', 'annual' => $amount / 12,
                    'week', 'weekly' => $amount * 52 / 12,
                    'day', 'daily' => $amount * 365 / 12,
                    default => $amount,
                };
            });
    }

    private function churn(int $teamId, CarbonImmutable $start, CarbonImmutable $end): float
    {
        if (! Schema::hasTable('billing_subscriptions')) {
            return 0.0;
        }
        $base = $this->database->table('billing_subscriptions')->where('team_id', $teamId)->where('starts_at', '<', $start)->count();
        $cancelled = $this->database->table('billing_subscriptions')->where('team_id', $teamId)->whereBetween('cancelled_at', [$start, $end])->count();

        return $base > 0 ? ($cancelled / $base) * 100 : 0.0;
    }

    private function invoiceSum(int $teamId, CarbonImmutable $at, string $kind, ?string $currency, ?CarbonImmutable $start = null): float
    {
        if (! Schema::hasTable('billing_invoices')) {
            return 0.0;
        }
        $query = $this->database->table('billing_invoices')->where('team_id', $teamId)->when($currency !== null, fn ($query) => $query->where('currency', $currency));
        if ($kind === 'aging') {
            return (float) $query->where('status', 'finalized')->whereNotNull('due_at')->where('due_at', '<=', $at)->sum('total_minor');
        }

        $column = $kind === 'tax' ? 'tax_minor' : 'total_minor';

        return (float) $query->whereIn('status', ['finalized', 'paid'])->whereBetween('finalized_at', [$start ?? $at->startOfDay(), $at->endOfDay()])->sum($column);
    }

    private function usageSum(int $teamId, CarbonImmutable $start, CarbonImmutable $end, ?string $currency): float
    {
        if (! Schema::hasTable('billing_usage_records')) {
            return 0.0;
        }

        return (float) $this->database->table('billing_usage_records')->where('team_id', $teamId)->whereBetween('occurred_at', [$start, $end->endOfDay()])->when($currency !== null, fn ($query) => $query->where('currency', $currency))->sum('amount_minor');
    }

    private function collectionSum(int $teamId, CarbonImmutable $at, ?string $currency): float
    {
        if (! Schema::hasTable('billing_collection_cases')) {
            return 0.0;
        }

        return (float) $this->database->table('billing_collection_cases')->where('team_id', $teamId)->whereNotIn('status', ['recovered', 'written_off', 'closed'])->where('created_at', '<=', $at)->when($currency !== null, fn ($query) => $query->where('currency', $currency))->sum('amount_minor');
    }

    private function providerSum(int $teamId, CarbonImmutable $start, CarbonImmutable $end, ?string $currency): float
    {
        if (! Schema::hasTable('billing_payments')) {
            return 0.0;
        }

        return (float) $this->database->table('billing_payments')->where('team_id', $teamId)->whereIn('status', ['captured', 'succeeded', 'paid'])->whereBetween('captured_at', [$start, $end->endOfDay()])->when($currency !== null, fn ($query) => $query->where('currency', $currency))->selectRaw('COALESCE(SUM(amount_minor - refunded_minor), 0) as total')->value('total');
    }

    private function countRows(string $table, int $teamId, CarbonImmutable $at, array $where): float
    {
        if (! Schema::hasTable($table)) {
            return 0.0;
        }

        return (float) $this->database->table($table)->where('team_id', $teamId)->where($where)->where('created_at', '<=', $at)->count();
    }
}
