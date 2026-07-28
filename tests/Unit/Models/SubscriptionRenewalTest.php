<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubscriptionRenewalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{cycle: string, expected: string}>
     */
    public static function billingCycleProvider(): array
    {
        return [
            'monthly' => ['cycle' => 'monthly', 'expected' => '2026-07-26'],
            'quarterly' => ['cycle' => 'quarterly', 'expected' => '2026-09-26'],
            'semi-annually' => ['cycle' => 'semi-annually', 'expected' => '2026-12-26'],
            'annually' => ['cycle' => 'annually', 'expected' => '2027-06-26'],
            'daily' => ['cycle' => 'daily', 'expected' => '2026-06-27'],
            'weekly' => ['cycle' => 'weekly', 'expected' => '2026-07-03'],
            'biennially' => ['cycle' => 'biennially', 'expected' => '2028-06-26'],
            'triennially' => ['cycle' => 'triennially', 'expected' => '2029-06-26'],
        ];
    }

    public function test_custom_cycle_advances_by_configured_number_of_days(): void
    {
        $subscription = Subscription::factory()->create([
            'renewal_period' => 'custom',
            'custom_period_days' => 45,
            'end_date' => Carbon::parse('2026-06-26'),
            'status' => 'active',
            'auto_renew' => true,
        ]);

        $this->assertTrue($subscription->renew());
        $this->assertSame('2026-08-10', $subscription->end_date->format('Y-m-d'));
    }

    public function test_one_time_subscription_cannot_renew(): void
    {
        $subscription = Subscription::factory()->create([
            'renewal_period' => 'one-time',
            'end_date' => Carbon::parse('2026-06-26'),
            'status' => 'active',
            'auto_renew' => true,
        ]);

        $this->assertFalse($subscription->renew());
    }

    #[DataProvider('billingCycleProvider')]
    public function test_renew_advances_end_date_by_billing_cycle(string $cycle, string $expected): void
    {
        $subscription = Subscription::factory()->create([
            'renewal_period' => $cycle,
            'end_date' => Carbon::parse('2026-06-26'),
            'status' => 'active',
            'auto_renew' => true,
        ]);

        $this->assertTrue($subscription->renew());
        $this->assertSame($expected, $subscription->end_date->format('Y-m-d'));
        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->last_billed_at);
    }

    public function test_cancelled_subscription_does_not_renew(): void
    {
        $subscription = Subscription::factory()->create([
            'renewal_period' => 'monthly',
            'end_date' => Carbon::parse('2026-06-26'),
            'status' => 'cancelled',
            'auto_renew' => true,
        ]);

        $this->assertFalse($subscription->renew());
        $this->assertSame('2026-06-26', $subscription->end_date->format('Y-m-d'));
    }

    public function test_subscription_without_auto_renew_does_not_renew(): void
    {
        $subscription = Subscription::factory()->create([
            'renewal_period' => 'monthly',
            'end_date' => Carbon::parse('2026-06-26'),
            'status' => 'active',
            'auto_renew' => false,
        ]);

        $this->assertFalse($subscription->renew());
        $this->assertSame('2026-06-26', $subscription->end_date->format('Y-m-d'));
    }
}
