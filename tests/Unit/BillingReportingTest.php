<?php

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Liberu\Billing\Reporting\Actions\CalculateReportingMetric;

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
