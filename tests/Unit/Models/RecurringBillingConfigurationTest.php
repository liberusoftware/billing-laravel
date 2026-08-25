<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\RecurringBillingConfiguration;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * calculateNextBillingDate() is pure date arithmetic with three branches:
 * a billing_day still ahead in the current month stays in-month; otherwise it
 * advances by frequency and (if set) pins the billing day. Clock is frozen so
 * the assertions are deterministic.
 */
class RecurringBillingConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-07-10 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function config(string $frequency, ?int $billingDay): RecurringBillingConfiguration
    {
        $config = new RecurringBillingConfiguration();
        $config->frequency = $frequency;
        $config->billing_day = $billingDay;

        return $config;
    }

    public function test_billing_day_still_ahead_this_month_stays_in_month(): void
    {
        // day 20 > today's day 10, so it does NOT advance the month.
        $date = $this->config('monthly', 20)->calculateNextBillingDate();

        $this->assertSame('2026-07-20', $date->toDateString());
    }

    public function test_passed_billing_day_advances_one_month_then_pins_the_day(): void
    {
        // day 5 <= today's day 10, so advance monthly then set the day.
        $date = $this->config('monthly', 5)->calculateNextBillingDate();

        $this->assertSame('2026-08-05', $date->toDateString());
    }

    public function test_monthly_without_billing_day_adds_one_month(): void
    {
        $date = $this->config('monthly', null)->calculateNextBillingDate();

        $this->assertSame('2026-08-10', $date->toDateString());
    }

    public function test_quarterly_adds_three_months(): void
    {
        $date = $this->config('quarterly', null)->calculateNextBillingDate();

        $this->assertSame('2026-10-10', $date->toDateString());
    }

    public function test_yearly_adds_one_year(): void
    {
        $date = $this->config('yearly', null)->calculateNextBillingDate();

        $this->assertSame('2027-07-10', $date->toDateString());
    }

    public function test_unknown_frequency_defaults_to_monthly(): void
    {
        $date = $this->config('weekly', null)->calculateNextBillingDate();

        $this->assertSame('2026-08-10', $date->toDateString());
    }
}
