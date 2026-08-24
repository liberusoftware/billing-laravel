<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Pricing\Actions\CapturePricingSnapshot;
use Liberu\Billing\Pricing\Actions\CreatePricingPlan;
use Liberu\Billing\Pricing\Enums\PricingModel;
use Liberu\Billing\Pricing\Enums\PricingPlanStatus;

uses(RefreshDatabase::class);

it('creates recurring and tiered plans with normalized money metadata', function () {
    $recurring = app(CreatePricingPlan::class)->execute([
        'name' => ' Pro ', 'pricing_model' => 'recurring', 'currency' => 'usd', 'unit_amount_minor' => 2500,
        'billing_interval' => 'monthly', 'team_id' => null,
    ]);
    $tiered = app(CreatePricingPlan::class)->execute([
        'name' => 'Usage', 'pricing_model' => 'tiered', 'currency' => 'eur', 'unit_amount_minor' => 0,
        'tiers' => [['up_to' => 10, 'unit_amount_minor' => 100]],
    ]);

    expect($recurring->pricing_model)->toBe(PricingModel::Recurring)
        ->and($recurring->currency)->toBe('USD')->and($recurring->status)->toBe(PricingPlanStatus::Draft)
        ->and($tiered->tiers)->toHaveCount(1);
});

it('captures immutable sequential pricing snapshots', function () {
    $plan = app(CreatePricingPlan::class)->execute([
        'name' => 'Snapshot plan', 'pricing_model' => 'one_time', 'currency' => 'USD', 'unit_amount_minor' => 1000,
    ]);

    $capture = app(CapturePricingSnapshot::class);
    $first = $capture->execute($plan);
    $second = $capture->execute($plan);

    expect($first->version)->toBe(1)->and($second->version)->toBe(2)
        ->and($second->payload['name'])->toBe('Snapshot plan');
});

it('rejects negative amounts and empty tier definitions', function () {
    $action = app(CreatePricingPlan::class);
    expect(fn () => $action->execute(['name' => 'Invalid', 'pricing_model' => 'recurring', 'currency' => 'USD', 'unit_amount_minor' => -1]))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $action->execute(['name' => 'Invalid', 'pricing_model' => 'tiered', 'currency' => 'USD', 'unit_amount_minor' => 0, 'tiers' => []]))
        ->toThrow(InvalidArgumentException::class);
});
