<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuoteService $quoteService;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->quoteService = new QuoteService;
        $this->customer = Customer::factory()->create();
    }

    public function test_can_create_quote(): void
    {
        $quote = $this->quoteService->createQuote([
            'customer_id' => $this->customer->id,
            'title' => 'Web Hosting Package',
            'currency' => 'USD',
            'items' => [
                ['description' => 'Hosting Plan', 'quantity' => 1, 'unit_price' => 50.00],
                ['description' => 'Domain Registration', 'quantity' => 1, 'unit_price' => 15.00],
            ],
        ]);

        $this->assertInstanceOf(Quote::class, $quote);
        $this->assertEquals('Web Hosting Package', $quote->title);
        $this->assertEquals('draft', $quote->status);
        $this->assertEquals($this->customer->id, $quote->customer_id);
        $this->assertCount(2, $quote->items);
        $this->assertEquals(65.00, $quote->total);
    }

    public function test_quote_number_is_auto_generated(): void
    {
        $quote = $this->quoteService->createQuote([
            'customer_id' => $this->customer->id,
            'title' => 'Test Quote',
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 100.00],
            ],
        ]);

        $this->assertNotNull($quote->quote_number);
        $this->assertStringStartsWith('QUO-', $quote->quote_number);
    }

    public function test_can_update_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Original Title',
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);

        $updated = $this->quoteService->updateQuote($quote, [
            'title' => 'Updated Title',
            'items' => [
                ['description' => 'New Item', 'quantity' => 2, 'unit_price' => 25.00],
            ],
        ]);

        $this->assertEquals('Updated Title', $updated->title);
        $this->assertEquals(50.00, $updated->total);
    }

    public function test_can_send_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Test Quote',
            'status' => 'draft',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
        ]);

        $sent = $this->quoteService->sendQuote($quote);

        $this->assertEquals('sent', $sent->status);
        $this->assertNotNull($sent->sent_at);
    }

    public function test_can_mark_quote_as_viewed(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Test Quote',
            'status' => 'sent',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
        ]);

        $viewed = $this->quoteService->markViewed($quote);

        $this->assertEquals('viewed', $viewed->status);
        $this->assertNotNull($viewed->viewed_at);
    }

    public function test_can_accept_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Test Quote',
            'status' => 'sent',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
        ]);

        $accepted = $this->quoteService->acceptQuote($quote);

        $this->assertEquals('accepted', $accepted->status);
        $this->assertNotNull($accepted->accepted_at);
        $this->assertTrue($accepted->canBeConverted());
    }

    public function test_can_decline_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Test Quote',
            'status' => 'sent',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
        ]);

        $declined = $this->quoteService->declineQuote($quote);

        $this->assertEquals('declined', $declined->status);
        $this->assertNotNull($declined->declined_at);
        $this->assertFalse($declined->canBeConverted());
    }

    public function test_can_convert_accepted_quote_to_invoice(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Test Quote',
            'status' => 'accepted',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
            'currency' => 'USD',
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'description' => 'Service A',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $invoice = $this->quoteService->convertToInvoice($quote);

        $this->assertNotNull($invoice);
        $this->assertEquals($this->customer->id, $invoice->customer_id);
        $this->assertEquals(100.00, $invoice->total_amount);
    }

    public function test_convert_non_accepted_quote_throws_exception(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Test Quote',
            'status' => 'sent',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->quoteService->convertToInvoice($quote);
    }

    public function test_can_expire_overdue_quotes(): void
    {
        Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Overdue Quote',
            'status' => 'sent',
            'valid_until' => now()->subDay(),
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
        ]);

        Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Valid Quote',
            'status' => 'sent',
            'valid_until' => now()->addDay(),
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
        ]);

        $expired = $this->quoteService->expireOverdueQuotes();

        $this->assertEquals(1, $expired);
        $this->assertDatabaseHas('quotes', [
            'title' => 'Overdue Quote',
            'status' => 'expired',
        ]);
        $this->assertDatabaseHas('quotes', [
            'title' => 'Valid Quote',
            'status' => 'sent',
        ]);
    }

    public function test_can_get_statistics(): void
    {
        Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Draft Quote',
            'status' => 'draft',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
        ]);

        Quote::create([
            'customer_id' => $this->customer->id,
            'title' => 'Accepted Quote',
            'status' => 'accepted',
            'subtotal' => 200,
            'tax_amount' => 0,
            'total' => 200,
        ]);

        $stats = $this->quoteService->getStatistics();

        $this->assertEquals(1, $stats['draft']['count']);
        $this->assertEquals(1, $stats['accepted']['count']);
        $this->assertEquals(200, $stats['accepted']['value']);
    }

    public function test_is_expired_returns_true_for_past_valid_until(): void
    {
        $quote = new Quote([
            'status' => 'sent',
            'valid_until' => now()->subDay(),
        ]);

        $this->assertTrue($quote->isExpired());
    }

    public function test_is_expired_returns_false_for_accepted_quote(): void
    {
        $quote = new Quote([
            'status' => 'accepted',
            'valid_until' => now()->subDay(),
        ]);

        $this->assertFalse($quote->isExpired());
    }
}
