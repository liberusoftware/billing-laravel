<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;
use Liberu\Billing\Pricing\Enums\PricingModel;
use Liberu\Billing\Pricing\Enums\PricingPlanStatus;
use Liberu\Billing\Pricing\Models\PricingPlan;

final readonly class CreatePricingPlan
{
    public function __construct(private DatabaseManager $database) {}

    /** @param array<string, mixed> $attributes */
    public function execute(array $attributes): PricingPlan
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $currency = strtoupper((string) ($attributes['currency'] ?? ''));
        $model = PricingModel::tryFrom((string) ($attributes['pricing_model'] ?? ''));
        $amount = (int) ($attributes['unit_amount_minor'] ?? 0);

        if ($name === '' || $model === null || ! preg_match('/^[A-Z]{3}$/', $currency) || $amount < 0) {
            throw new \InvalidArgumentException('Pricing plan attributes are invalid.');
        }

        if ($model === PricingModel::Recurring && trim((string) ($attributes['billing_interval'] ?? '')) === '') {
            throw new \InvalidArgumentException('Recurring plans require a billing interval.');
        }

        if ($model === PricingModel::Usage && trim((string) ($attributes['usage_unit'] ?? '')) === '') {
            throw new \InvalidArgumentException('Usage plans require a usage unit.');
        }

        if ($model === PricingModel::Tiered && ($attributes['tiers'] ?? []) === []) {
            throw new \InvalidArgumentException('Pricing amount and tiers are invalid.');
        }

        $productId = $attributes['product_id'] ?? null;
        if ($productId !== null && Schema::hasTable('billing_catalog_products')) {
            $product = $this->database->table('billing_catalog_products')->where('id', (int) $productId)->first(['team_id']);
            $teamId = $attributes['team_id'] ?? null;
            if ($product === null || ($product->team_id !== null && ($teamId === null || (int) $product->team_id !== (int) $teamId))) {
                throw new \InvalidArgumentException('Pricing product reference is invalid.');
            }
        } elseif ($productId !== null) {
            throw new \InvalidArgumentException('Pricing product reference is invalid.');
        }

        return $this->database->transaction(fn (): PricingPlan => PricingPlan::query()->create([
            'team_id' => $attributes['team_id'] ?? null,
            'product_id' => $productId,
            'name' => $name,
            'pricing_model' => $model,
            'currency' => $currency,
            'unit_amount_minor' => $amount,
            'billing_interval' => $attributes['billing_interval'] ?? null,
            'usage_unit' => $attributes['usage_unit'] ?? null,
            'tiers' => $attributes['tiers'] ?? [],
            'metadata' => $attributes['metadata'] ?? [],
            'status' => PricingPlanStatus::Draft,
        ]));
    }
}
