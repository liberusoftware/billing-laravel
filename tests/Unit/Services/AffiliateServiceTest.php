<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Affiliate;
use App\Models\AffiliateTransaction;
use App\Models\User;
use App\Services\AffiliateReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateServiceTest extends TestCase
{
    use RefreshDatabase;

    private function affiliate(array $overrides = []): Affiliate
    {
        return Affiliate::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'code' => 'AFF-'.fake()->unique()->numerify('#####'),
            'commission_rate' => 10.00,
            'status' => 'active',
            'total_earnings' => 0,
        ], $overrides));
    }

    public function test_commission_rate_falls_back_to_default(): void
    {
        $affiliate = $this->affiliate(['commission_rate' => 15.00]);

        $this->assertEquals(15.00, (float) $affiliate->getCommissionRate());
    }

    public function test_commission_rate_uses_product_then_category_custom_rate(): void
    {
        $affiliate = $this->affiliate([
            'commission_rate' => 10.00,
            'custom_rates' => [
                'products' => [42 => 25.0],
                'categories' => [7 => 20.0],
            ],
        ]);

        $this->assertEquals(25.0, (float) $affiliate->getCommissionRate(42, 7));   // product wins
        $this->assertEquals(20.0, (float) $affiliate->getCommissionRate(99, 7));   // category fallback
        $this->assertEquals(10.0, (float) $affiliate->getCommissionRate(99, 99));  // default fallback
    }

    public function test_report_sums_commission_in_range_and_counts_referrals(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');
        $affiliate = $this->affiliate();

        // in-range commission transactions
        AffiliateTransaction::create(['affiliate_id' => $affiliate->id, 'amount' => 30.00, 'type' => 'commission', 'status' => 'pending']);
        AffiliateTransaction::create(['affiliate_id' => $affiliate->id, 'amount' => 20.00, 'type' => 'commission', 'status' => 'pending']);
        // non-commission type excluded from earnings
        AffiliateTransaction::create(['affiliate_id' => $affiliate->id, 'amount' => 99.00, 'type' => 'payout', 'status' => 'pending']);
        // out-of-range commission excluded
        $old = AffiliateTransaction::create(['affiliate_id' => $affiliate->id, 'amount' => 500.00, 'type' => 'commission', 'status' => 'pending']);
        $old->created_at = Carbon::parse('2026-01-01');
        $old->save();

        // one referral in range
        User::factory()->create(['referred_by' => $affiliate->id]);

        $report = app(AffiliateReportingService::class)->generateReport($affiliate, '2026-06-01', '2026-06-30');

        $this->assertEquals(50.00, (float) $report['total_earnings']); // 30 + 20 only
        $this->assertEquals(1, $report['total_referrals']);
        $this->assertCount(3, $report['transactions']); // 2 commission + 1 payout in range

        Carbon::setTestNow();
    }
}
