<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Products_Service;
use App\Models\Subscription;
use App\Models\UsageRecord;
use App\Services\BillingService;
use App\Services\UsageImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsageBillingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function import_usage_creates_an_unprocessed_record(): void
    {
        $subscription = $this->meteredSubscription();

        $record = app(UsageImportService::class)->importUsage($subscription, 'api_calls', 100);

        $this->assertFalse($record->processed);
        $this->assertSame('api_calls', $record->metric_name);
        $this->assertEquals(100, (float) $record->quantity);
        $this->assertEquals($subscription->id, $record->subscription_id);
    }

    #[Test]
    public function import_usage_accumulates_into_the_open_record(): void
    {
        $subscription = $this->meteredSubscription();
        $service = app(UsageImportService::class);

        $service->importUsage($subscription, 'api_calls', 40);
        $service->importUsage($subscription, 'api_calls', 60);

        $records = UsageRecord::where('subscription_id', $subscription->id)->get();
        $this->assertCount(1, $records);
        $this->assertEquals(100, (float) $records->first()->quantity);
    }

    #[Test]
    public function generate_invoice_bills_unprocessed_usage_and_marks_it_processed(): void
    {
        $subscription = $this->meteredSubscription();
        app(UsageImportService::class)->importUsage($subscription, 'api_calls', 100);

        $invoice = app(BillingService::class)->generateInvoice($subscription);

        // base 10.00 + 100 * 0.50 = 60.00
        $this->assertEquals(60.00, (float) $invoice->total_amount);
        $this->assertFalse(
            UsageRecord::where('subscription_id', $subscription->id)->where('processed', false)->exists()
        );
    }

    #[Test]
    public function second_invoice_does_not_double_bill_already_processed_usage(): void
    {
        $subscription = $this->meteredSubscription();
        $billing = app(BillingService::class);
        app(UsageImportService::class)->importUsage($subscription, 'api_calls', 100);

        $billing->generateInvoice($subscription);
        $second = $billing->generateInvoice($subscription);

        // usage already processed: only the base price remains
        $this->assertEquals(10.00, (float) $second->total_amount);
    }

    #[Test]
    public function usage_import_command_records_usage_for_active_metered_subscriptions(): void
    {
        $subscription = $this->meteredSubscription();

        $this->artisan('usage:import')->assertSuccessful();

        $this->assertTrue(UsageRecord::where('subscription_id', $subscription->id)->exists());
    }

    private function meteredSubscription(): Subscription
    {
        $product = Products_Service::factory()->create([
            'base_price' => '10.00',
            'pricing_model' => 'usage_based',
            'custom_pricing_data' => ['usage_config' => [
                'api_calls' => ['type' => 'per_unit', 'rate' => 0.5],
            ]],
        ]);

        return Subscription::factory()->create([
            'customer_id' => Customer::factory(),
            'product_service_id' => $product->id,
            'status' => 'active',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'last_billed_at' => null,
        ]);
    }
}
