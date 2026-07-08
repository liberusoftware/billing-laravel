<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Invoice_Item;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\UsageImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Money-safety guards: discounts may never push an invoice total below zero, and
 * usage imports may never record a negative quantity.
 */
class BillingHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function invoiceWorth(float $subtotal): Invoice
    {
        $invoice = Invoice::factory()->create([
            'currency' => 'USD',
            'tax_amount' => 0,
        ]);

        Invoice_Item::create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => $subtotal,
            'total_price' => $subtotal,
            'currency' => 'USD',
        ]);

        return $invoice->refresh();
    }

    private function discount(array $attributes): Discount
    {
        return Discount::create(array_merge([
            'code' => 'SAVE-'.fake()->unique()->numberBetween(1, 99999),
            'name' => 'Test',
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'used_count' => 0,
        ], $attributes));
    }

    public function test_over_100_percent_discount_floors_total_at_zero(): void
    {
        $invoice = $this->invoiceWorth(100);
        $discount = $this->discount(['type' => 'percentage', 'value' => '150']);

        $result = app(BillingService::class)->applyDiscount($invoice, $discount->code);
        $invoice->refresh();

        $this->assertTrue($result['success']);
        $this->assertEquals(100.0, (float) $invoice->discount_amount);
        $this->assertEquals(0.0, (float) $invoice->total_amount);
    }

    public function test_oversized_fixed_discount_floors_total_at_zero(): void
    {
        $invoice = $this->invoiceWorth(100);
        $discount = $this->discount(['type' => 'fixed', 'value' => '500', 'currency' => 'USD']);

        app(BillingService::class)->applyDiscount($invoice, $discount->code);
        $invoice->refresh();

        $this->assertEquals(100.0, (float) $invoice->discount_amount);
        $this->assertEquals(0.0, (float) $invoice->total_amount);
    }

    public function test_negative_usage_quantity_is_rejected(): void
    {
        $subscription = Subscription::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(UsageImportService::class)->importUsage($subscription, 'bandwidth', -5.0);
    }
}
