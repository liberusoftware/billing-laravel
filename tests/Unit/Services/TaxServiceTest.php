<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\TaxExemption;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.tax_api.enabled' => false]);
        $this->taxService = app(TaxService::class);
    }

    public function test_calculate_tax_returns_zero_for_exempt_customer(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        TaxExemption::create([
            'customer_id' => $customer->id,
            'is_active' => true,
            'expiry_date' => null,
            'reason' => 'Test exemption',
        ]);

        $tax = $this->taxService->calculateTax($invoice);

        $this->assertEquals(0, $tax);
    }

    public function test_calculate_tax_returns_numeric_value(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $tax = $this->taxService->calculateTax($invoice);

        $this->assertIsNumeric($tax);
        $this->assertGreaterThanOrEqual(0, $tax);
    }

    public function test_service_instantiates_correctly(): void
    {
        $service = app(TaxService::class);
        $this->assertInstanceOf(TaxService::class, $service);
    }
}
