<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\OrganisationType;
use App\Models\Subscription;
use App\Models\Team;
use App\Services\ResellerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ResellerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseller_pricing_supports_default_and_product_specific_rates(): void
    {
        [$provider, $reseller] = $this->organisations();
        $agreement = app(ResellerService::class)->createAgreement($provider, $reseller, [
            'default_discount_percent' => 20,
            'revenue_share_percent' => 30,
            'product_pricing' => [
                '10' => ['price' => 55],
                '11' => ['discount_percent' => 35],
            ],
        ]);

        $service = app(ResellerService::class);
        $this->assertSame(80.0, $service->wholesalePrice($agreement, 100));
        $this->assertSame(55.0, $service->wholesalePrice($agreement, 100, 10));
        $this->assertSame(65.0, $service->wholesalePrice($agreement, 100, 11));
    }

    public function test_service_delegation_reserves_credit_and_revenue_is_shared(): void
    {
        [$provider, $reseller] = $this->organisations();
        $agreement = app(ResellerService::class)->createAgreement($provider, $reseller, [
            'default_discount_percent' => 25,
            'revenue_share_percent' => 20,
            'credit_limit' => 100,
            'currency' => 'USD',
        ]);
        $subscription = Subscription::factory()->create([
            'team_id' => $provider->id,
            'price' => 100,
            'currency' => 'USD',
        ]);

        $delegation = app(ResellerService::class)->delegate($agreement, $subscription);
        $this->assertEquals(75, (float) $delegation->wholesale_price);
        $this->assertEquals(75, (float) $agreement->refresh()->credit_used);

        $transaction = app(ResellerService::class)->recordRevenue($delegation, 100);
        $this->assertEquals(20, (float) $transaction->reseller_amount);
        $this->assertEquals(80, (float) $transaction->provider_amount);

        $settled = app(ResellerService::class)->settle($transaction);
        $this->assertSame('settled', $settled->status);
        $this->assertNotNull($settled->settled_at);
    }

    public function test_credit_limit_prevents_over_delegation(): void
    {
        [$provider, $reseller] = $this->organisations();
        $agreement = app(ResellerService::class)->createAgreement($provider, $reseller, [
            'default_discount_percent' => 0,
            'credit_limit' => 50,
        ]);
        $subscription = Subscription::factory()->create(['team_id' => $provider->id, 'price' => 60]);

        $this->expectException(RuntimeException::class);
        app(ResellerService::class)->delegate($agreement, $subscription);
    }

    /** @return array{Team, Team} */
    private function organisations(): array
    {
        $provider = Team::factory()->create(['organisation_type' => OrganisationType::Company]);
        $reseller = Team::factory()->create([
            'parent_team_id' => $provider->id,
            'organisation_type' => OrganisationType::Reseller,
        ]);

        return [$provider, $reseller];
    }
}
