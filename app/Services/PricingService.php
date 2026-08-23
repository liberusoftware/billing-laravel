<?php

namespace App\Services;

use App\Models\Products_Service;
use App\Models\UsageRecord;

class PricingService
{
    public function calculatePrice(Products_Service $product, array $options = [])
    {
        $basePrice = $product->base_price;

        return match ($product->pricing_model) {
            'fixed' => $basePrice,
            'tiered' => $this->calculateTieredPrice(
                $product,
                $options
            ),
            'usage_based' => $this->calculateUsageBasedPrice(
                $product,
                $options
            ),
            default => $basePrice,
        };
    }

    private function calculateTieredPrice(Products_Service $product, array $options)
    {
        $tiers = $product->custom_pricing_data['tiers'] ?? [];
        $usage = $options['usage'] ?? 0;

        foreach ($tiers as $tier) {
            if ($usage <= $tier['max_usage']) {
                return $tier['price'];
            }
        }

        return end($tiers)['price'] ?? $product->base_price;
    }

    private function calculateUsageBasedPrice(Products_Service $product, array $options)
    {
        $subscriptionId = $options['subscription_id'] ?? null;
        $startDate = $options['start_date'] ?? null;
        $endDate = $options['end_date'] ?? null;

        if (! $subscriptionId || ! $startDate || ! $endDate) {
            return $product->base_price;
        }

        $usageConfig = $product->custom_pricing_data['usage_config'] ?? [];
        $basePrice = $product->base_price;
        $totalPrice = $basePrice;

        // Get usage records for the billing period
        $usage = UsageRecord::where(
            'subscription_id',
            $subscriptionId
        )
            ->whereBetween(
                'recorded_at',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->where(
                'processed',
                false
            )
            ->get();

        foreach ($usageConfig as $metric => $pricing) {
            $metricUsage = (float) $usage->where(
                'metric_name',
                $metric
            )->sum('quantity');

            if ($pricing['type'] === 'per_unit') {
                $totalPrice += $metricUsage * $pricing['rate'];
            } elseif ($pricing['type'] === 'tiered') {
                $totalPrice += $this->calculateTieredMetricPrice(
                    $metricUsage,
                    $pricing['tiers']
                );
            }
        }

        // Money math: pin to cents so float accumulation can't leak sub-cent dust.
        return round($totalPrice, 2);
    }

    /**
     * Graduated tiered pricing. `max_usage` is the cumulative ceiling of each
     * tier (matching calculateTieredPrice), so a tier only bills the slice of
     * usage between the previous ceiling and its own.
     *
     * @param  array<int, array{max_usage: int|float, rate: int|float}>  $tiers
     */
    private function calculateTieredMetricPrice(float $usage, array $tiers): float
    {
        $price = 0.0;
        $remainingUsage = $usage;
        $previousMax = 0;
        $lastTier = array_key_last($tiers);

        foreach ($tiers as $index => $tier) {
            // Last tier is unbounded: it absorbs whatever usage is left.
            $tierWidth = $index === $lastTier
                ? $remainingUsage
                : max(0, $tier['max_usage'] - $previousMax);

            $tierUsage = min($remainingUsage, $tierWidth);
            $price += $tierUsage * $tier['rate'];
            $remainingUsage -= $tierUsage;
            $previousMax = $tier['max_usage'];

            if ($remainingUsage <= 0) {
                break;
            }
        }

        return $price;
    }
}
