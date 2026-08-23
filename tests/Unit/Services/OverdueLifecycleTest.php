<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LateFeeConfiguration;
use App\Models\Products_Service;
use App\Models\ServiceSuspension;
use App\Models\Subscription;
use App\Models\Team;
use App\Services\ServiceAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_late_fee_applied_to_overdue_invoice(): void
    {
        $team = Team::factory()->create();
        $invoice = Invoice::factory()->create([
            'status' => 'pending',
            'total_amount' => 100.00,
            'due_date' => now()->subDays(10),
        ]);
        $invoice->team_id = $team->id;
        $invoice->save();

        LateFeeConfiguration::create([
            'team_id' => $team->id,
            'fee_type' => 'fixed',
            'fee_amount' => 10.00,
            'grace_period_days' => 0,
            'frequency' => 'one-time',
            'is_compound' => false,
        ]);

        $fee = $invoice->applyLateFee();

        $this->assertEquals(10.00, (float) $fee);
        $this->assertEquals(10.00, (float) $invoice->fresh()->late_fee_amount);
    }

    public function test_no_late_fee_within_grace_period(): void
    {
        $team = Team::factory()->create();
        $invoice = Invoice::factory()->create([
            'status' => 'pending',
            'total_amount' => 100.00,
            'due_date' => now()->subDays(2),
        ]);
        $invoice->team_id = $team->id;
        $invoice->save();

        LateFeeConfiguration::create([
            'team_id' => $team->id,
            'fee_type' => 'fixed',
            'fee_amount' => 10.00,
            'grace_period_days' => 7,
            'frequency' => 'one-time',
            'is_compound' => false,
        ]);

        $this->assertEquals(0, $invoice->applyLateFee());
    }

    public function test_auto_unsuspend_on_payment_reactivates_overdue_suspension(): void
    {
        $customer = Customer::factory()->create();
        $productService = Products_Service::factory()->create();
        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'product_service_id' => $productService->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'status' => 'suspended',
            'renewal_period' => 'monthly',
        ]);
        ServiceSuspension::create([
            'subscription_id' => $subscription->id,
            'reason' => 'overdue_payment',
            'suspended_at' => now()->subDays(3),
            'is_active' => true,
        ]);
        $invoice = Invoice::factory()->create([
            'status' => 'paid',
            'subscription_id' => $subscription->id,
        ]);

        $result = app(ServiceAutomationService::class)->autoUnsuspendOnPayment($invoice);

        $this->assertTrue($result);
        $this->assertEquals('active', $subscription->fresh()->status);
    }

    public function test_auto_unsuspend_skips_unpaid_invoice(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'pending']);

        $this->assertFalse(app(ServiceAutomationService::class)->autoUnsuspendOnPayment($invoice));
    }
}
