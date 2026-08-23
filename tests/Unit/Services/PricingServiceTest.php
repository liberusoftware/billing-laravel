<?php

namespace Tests\Unit\Services;

use App\Models\Products_Service;
use App\Models\UsageRecord;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new PricingService;
    }

    #[Test]
    public function fixed_pricing_returns_base_price(): void
    {
        $product = Products_Service::factory()->create([
            'base_price' => '49.99',
            'pricing_model' => 'fixed',
        ]);

        $this->assertEquals(49.99, (float) $this->pricing->calculatePrice($product));
    }

    #[Test]
    public function unknown_pricing_model_falls_back_to_base_price(): void
    {
        $product = Products_Service::factory()->create([
            'base_price' => '12.00',
            'pricing_model' => 'something_else',
        ]);

        $this->assertEquals(12.00, (float) $this->pricing->calculatePrice($product));
    }

    #[Test]
    public function tiered_pricing_returns_flat_price_of_matching_tier(): void
    {
        $product = Products_Service::factory()->create([
            'base_price' => '5.00',
            'pricing_model' => 'tiered',
            'custom_pricing_data' => ['tiers' => [
                ['max_usage' => 10, 'price' => 10],
                ['max_usage' => 50, 'price' => 40],
                ['max_usage' => 100, 'price' => 70],
            ]],
        ]);

        // usage 25 falls into the 50-ceiling tier
        $this->assertEquals(40.0, (float) $this->pricing->calculatePrice($product, ['usage' => 25]));
        // boundary: usage == ceiling stays in that tier
        $this->assertEquals(10.0, (float) $this->pricing->calculatePrice($product, ['usage' => 10]));
        // beyond all tiers falls back to last tier price
        $this->assertEquals(70.0, (float) $this->pricing->calculatePrice($product, ['usage' => 999]));
    }

    #[Test]
    public function usage_based_returns_base_price_when_period_options_missing(): void
    {
        $product = Products_Service::factory()->create([
            'base_price' => '8.00',
            'pricing_model' => 'usage_based',
            'custom_pricing_data' => ['usage_config' => [
                'api_calls' => ['type' => 'per_unit', 'rate' => 0.01],
            ]],
        ]);

        $this->assertEquals(8.00, (float) $this->pricing->calculatePrice($product));
    }

    #[Test]
    public function usage_based_per_unit_adds_quantity_times_rate_to_base(): void
    {
        $product = Products_Service::factory()->create([
            'base_price' => '10.00',
            'pricing_model' => 'usage_based',
            'custom_pricing_data' => ['usage_config' => [
                'api_calls' => ['type' => 'per_unit', 'rate' => 0.05],
            ]],
        ]);

        // 60 + 40 = 100 units @ 0.05 = 5.00 ; + base 10.00 = 15.00
        $this->seedUsage(1, 'api_calls', [60, 40]);

        $price = $this->pricing->calculatePrice($product, $this->period(1));

        $this->assertEquals(15.00, (float) $price);
    }

    #[Test]
    public function usage_based_graduated_tiers_bill_each_slice_at_its_own_rate(): void
    {
        $product = Products_Service::factory()->create([
            'base_price' => '0.00',
            'pricing_model' => 'usage_based',
            'custom_pricing_data' => ['usage_config' => [
                'storage' => ['type' => 'tiered', 'tiers' => [
                    ['max_usage' => 100, 'rate' => 1.0],
                    ['max_usage' => 150, 'rate' => 0.5],
                    ['max_usage' => 1000, 'rate' => 0.1],
                ]],
            ]],
        ]);

        // usage 200: 100*1.0 + 50*0.5 + 50*0.1 = 130.00
        $this->seedUsage(1, 'storage', [200]);

        $price = $this->pricing->calculatePrice($product, $this->period(1));

        $this->assertEquals(130.00, (float) $price);
    }

    #[Test]
    public function usage_based_only_counts_unprocessed_records_inside_the_period(): void
    {
        $product = Products_Service::factory()->create([
            'base_price' => '0.00',
            'pricing_model' => 'usage_based',
            'custom_pricing_data' => ['usage_config' => [
                'api_calls' => ['type' => 'per_unit', 'rate' => 1.0],
            ]],
        ]);

        // counted: 30 in-range + unprocessed
        UsageRecord::create([
            'subscription_id' => 1, 'metric_name' => 'api_calls',
            'quantity' => 30, 'recorded_at' => '2026-01-15', 'processed' => false,
        ]);
        // excluded: already processed
        UsageRecord::create([
            'subscription_id' => 1, 'metric_name' => 'api_calls',
            'quantity' => 999, 'recorded_at' => '2026-01-16', 'processed' => true,
        ]);
        // excluded: outside the billing window
        UsageRecord::create([
            'subscription_id' => 1, 'metric_name' => 'api_calls',
            'quantity' => 999, 'recorded_at' => '2025-12-01', 'processed' => false,
        ]);

        $price = $this->pricing->calculatePrice($product, [
            'subscription_id' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]);

        $this->assertEquals(30.00, (float) $price);
    }

    /**
     * @param  array<int, int|float>  $quantities
     */
    private function seedUsage(int $subscriptionId, string $metric, array $quantities): void
    {
        foreach ($quantities as $quantity) {
            UsageRecord::create([
                'subscription_id' => $subscriptionId,
                'metric_name' => $metric,
                'quantity' => $quantity,
                'recorded_at' => '2026-01-15',
                'processed' => false,
            ]);
        }
    }

    /**
     * @return array{subscription_id: int, start_date: string, end_date: string}
     */
    private function period(int $subscriptionId): array
    {
        return [
            'subscription_id' => $subscriptionId,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ];
    }
}
