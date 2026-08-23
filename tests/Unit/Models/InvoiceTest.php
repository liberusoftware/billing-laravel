<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_created_via_factory(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertNotNull($invoice->id);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertNotNull($invoice->customer_id);
    }

    public function test_invoice_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $invoice->customer);
        $this->assertEquals($customer->id, $invoice->customer->id);
    }

    public function test_invoice_pending_scope_filters_correctly(): void
    {
        Invoice::factory()->create(['status' => 'pending']);
        Invoice::factory()->create(['status' => 'paid']);
        Invoice::factory()->create(['status' => 'overdue']);

        $pending = Invoice::where('status', 'pending')->get();

        $this->assertCount(1, $pending);
        $this->assertEquals('pending', $pending->first()->status);
    }

    public function test_invoice_paid_query_filters_correctly(): void
    {
        Invoice::factory()->create(['status' => 'paid']);
        Invoice::factory()->create(['status' => 'pending']);

        $paid = Invoice::where('status', 'paid')->get();

        $this->assertCount(1, $paid);
        $this->assertEquals('paid', $paid->first()->status);
    }

    public function test_invoice_status_can_be_updated(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'pending']);

        $invoice->update(['status' => 'paid']);

        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    public function test_invoice_has_correct_fillable_attributes(): void
    {
        $invoice = new Invoice;
        $fillable = $invoice->getFillable();

        $this->assertContains('customer_id', $fillable);
        $this->assertContains('invoice_number', $fillable);
        $this->assertContains('total_amount', $fillable);
        $this->assertContains('status', $fillable);
    }
}
