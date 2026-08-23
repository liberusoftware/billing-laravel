<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Products_Service;
use App\Models\ServiceSuspension;
use App\Models\Subscription;
use App\Services\ServiceAutomationService;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ServiceAutomationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ServiceAutomationService $automationService;

    protected function setUp(): void
    {
        parent::setUp();

        $webhookService = Mockery::mock(WebhookService::class);
        $webhookService->shouldReceive('dispatch')->andReturn(null);

        $this->automationService = new ServiceAutomationService($webhookService);
    }

    public function test_can_suspend_overdue_services(): void
    {
        $customer = Customer::factory()->create();
        $productService = Products_Service::factory()->create();

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'product_service_id' => $productService->id,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonth(),
            'status' => 'active',
            'renewal_period' => 'monthly',
        ]);

        Invoice::create([
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-001',
            'issue_date' => now()->subDays(10),
            'due_date' => now()->subDays(8),
            'total_amount' => 100,
            'status' => 'pending',
        ]);

        $suspended = $this->automationService->suspendOverdueServices(7);

        $this->assertEquals(1, $suspended);
        $this->assertEquals('suspended', $subscription->fresh()->status);
        $this->assertDatabaseHas('service_suspensions', [
            'subscription_id' => $subscription->id,
            'reason' => 'overdue_payment',
            'is_active' => true,
        ]);
    }

    public function test_can_unsuspend_service(): void
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

        $suspension = ServiceSuspension::create([
            'subscription_id' => $subscription->id,
            'reason' => 'overdue_payment',
            'suspended_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        $result = $this->automationService->unsuspendService($subscription);

        $this->assertTrue($result);
        $this->assertEquals('active', $subscription->fresh()->status);
        $this->assertFalse($suspension->fresh()->is_active);
        $this->assertNotNull($suspension->fresh()->unsuspended_at);
    }

    public function test_can_terminate_service(): void
    {
        $customer = Customer::factory()->create();
        $productService = Products_Service::factory()->create();

        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'product_service_id' => $productService->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
            'renewal_period' => 'monthly',
        ]);

        $result = $this->automationService->terminateService($subscription);

        $this->assertTrue($result);
        $this->assertEquals('terminated', $subscription->fresh()->status);
        $this->assertNotNull($subscription->fresh()->ends_at);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
