<?php

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Liberu\Billing\Reporting\Actions\CalculateReportingMetric;
use Liberu\Billing\Reporting\Actions\CreateMetricSnapshot;
use Liberu\Billing\Reporting\Actions\ExportReportingMetrics;
use Liberu\Billing\Reporting\Actions\RecordReportingMetric;
use Liberu\Billing\Reporting\Events\MetricSnapshotCreated;
use Liberu\Billing\Reporting\Events\ReportingMetricRecorded;

uses(RefreshDatabase::class);

it('calculates recurring, invoice, and provider metrics from billing tables', function () {
    $now = CarbonImmutable::parse('2026-08-25 12:00:00');
    $plan = DB::table('billing_pricing_plans')->insertGetId([
        'team_id' => 10, 'name' => 'Pro', 'pricing_model' => 'flat', 'currency' => 'USD',
        'unit_amount_minor' => 12000, 'billing_interval' => 'yearly', 'status' => 'active',
        'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table('billing_subscriptions')->insert([
        'team_id' => 10, 'pricing_plan_id' => $plan, 'status' => 'active', 'starts_at' => $now->subMonth(),
        'auto_renew' => true, 'id_protection' => false, 'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table('billing_invoices')->insert([
        'team_id' => 10, 'status' => 'paid', 'currency' => 'USD', 'subtotal_minor' => 1000,
        'tax_minor' => 200, 'total_minor' => 1200, 'finalized_at' => $now, 'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table('billing_payments')->insert([
        'team_id' => 10, 'amount_minor' => 1200, 'currency' => 'USD', 'status' => 'captured',
        'gateway' => 'test', 'captured_at' => $now, 'refunded_minor' => 200, 'created_at' => $now, 'updated_at' => $now,
    ]);

    $calculator = app(CalculateReportingMetric::class);

    expect($calculator->execute(10, 'mrr', $now->startOfMonth(), $now, 'USD')['value'])->toBe(1000.0)
        ->and($calculator->execute(10, 'arr', $now->startOfMonth(), $now, 'USD')['value'])->toBe(12000.0)
        ->and($calculator->execute(10, 'tax', $now->startOfMonth(), $now, 'USD')['value'])->toBe(200.0)
        ->and($calculator->execute(10, 'provider', $now->startOfMonth(), $now, 'USD')['value'])->toBe(1000.0);
});

it('returns zero for optional metric tables that are not installed', function () {
    $period = CarbonImmutable::parse('2026-08-25');

    expect(app(CalculateReportingMetric::class)->execute(10, 'usage', $period, $period)['value'])->toBe(0.0);
});

it('excludes draft invoices from aging receivables', function (): void {
    $dueAt = CarbonImmutable::parse('2026-08-20');
    DB::table('billing_invoices')->insert([
        ['team_id' => 10, 'status' => 'draft', 'currency' => 'USD', 'total_minor' => 900, 'due_at' => $dueAt, 'created_at' => $dueAt, 'updated_at' => $dueAt],
        ['team_id' => 10, 'status' => 'finalized', 'currency' => 'USD', 'total_minor' => 1200, 'due_at' => $dueAt, 'created_at' => $dueAt, 'updated_at' => $dueAt],
    ]);

    expect(app(CalculateReportingMetric::class)->execute(10, 'aging', $dueAt->addDay(), $dueAt->addDay(), 'USD')['value'])
        ->toBe(1200.0);
});

it('exports only the current team reporting metrics as CSV', function (): void {
    DB::table('billing_reporting_metrics')->insert([
        ['team_id' => 10, 'metric' => 'revenue', 'period_start' => '2026-08-01', 'period_end' => '2026-08-31', 'value' => 1250, 'currency' => 'USD', 'source' => 'test', 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => 10, 'metric' => 'tax', 'period_start' => '2026-08-01', 'period_end' => '2026-08-31', 'value' => 250, 'currency' => 'USD', 'source' => 'test', 'created_at' => now(), 'updated_at' => now()],
        ['team_id' => 11, 'metric' => 'revenue', 'period_start' => '2026-08-01', 'period_end' => '2026-08-31', 'value' => 9999, 'currency' => 'USD', 'source' => 'other', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $csv = app(ExportReportingMetrics::class)->execute(10, 'revenue');

    expect($csv)->toContain('metric,period_start,period_end,value,currency,source')
        ->toContain('revenue,2026-08-01,2026-08-31,1250,USD,test')
        ->not->toContain('tax,2026-08-01')
        ->not->toContain('9999');
});

it('dispatches reporting mutation events after commit', function (): void {
    Event::fake([MetricSnapshotCreated::class, ReportingMetricRecorded::class]);

    app(RecordReportingMetric::class)->execute(10, [
        'metric' => 'revenue',
        'period_start' => '2026-08-01',
        'period_end' => '2026-08-31',
        'value' => 1250,
        'currency' => 'USD',
    ]);
    app(CreateMetricSnapshot::class)->handle(10, ['name' => 'August revenue']);

    Event::assertDispatchedTimes(ReportingMetricRecorded::class, 1);
    Event::assertDispatchedTimes(MetricSnapshotCreated::class, 1);
});
