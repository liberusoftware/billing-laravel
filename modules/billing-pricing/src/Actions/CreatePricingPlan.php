<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Pricing\Enums\PricingModel;
use Liberu\Billing\Pricing\Enums\PricingPlanStatus;
use Liberu\Billing\Pricing\Models\PricingPlan;

final readonly class CreatePricingPlan
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): PricingPlan
    {
        $model = PricingModel::from($attributes['pricing_model']);
        $amount = (int) ($attributes['unit_amount_minor'] ?? 0);
        if ($amount < 0 || ($model === PricingModel::Tiered && $attributes['tiers'] === [])) {
            throw new \InvalidArgumentException('Pricing amount and tiers are invalid.');
        }

        return $this->database->transaction(fn (): PricingPlan => PricingPlan::query()->create([
            'team_id' => $attributes['team_id'] ?? null,
            'product_id' => $attributes['product_id'] ?? null,
            'name' => trim((string) $attributes['name']),
            'pricing_model' => $model,
            'currency' => strtoupper((string) $attributes['currency']),
            'unit_amount_minor' => $amount,
            'billing_interval' => $attributes['billing_interval'] ?? null,
            'usage_unit' => $attributes['usage_unit'] ?? null,
            'tiers' => $attributes['tiers'] ?? [],
            'metadata' => $attributes['metadata'] ?? [],
            'status' => PricingPlanStatus::Draft,
        ]));
    }
}
