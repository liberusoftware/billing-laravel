<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\HostingAccount;
use App\Models\Products_Service;
use App\Models\Subscription;
use App\Services\HostingService;
use App\Services\ServiceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ServiceProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $provisioningService;

    protected $hostingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hostingService = Mockery::mock(HostingService::class);
        $this->provisioningService = new ServiceProvisioningService($this->hostingService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_provision_hosting_service(): void
    {
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create([
            'type' => 'hosting',
            'name' => 'basic-hosting',
        ]);
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $product->id,
            'domain' => 'example.com',
        ]);

        $this->hostingService
            ->shouldReceive('provisionAccount')
            ->once()
            ->with(
                Mockery::on(fn ($account): bool => $account instanceof HostingAccount &&
                    $account->customer_id === $customer->id &&
                    $account->subscription_id === $subscription->id &&
                    $account->status === 'pending'
                ),
                Mockery::on(fn ($prod): bool => $prod->id === $product->id)
            )
            ->andReturnUsing(function ($account): true {
                $account->status = 'active';
                $account->save();

                return true;
            });

        $result = $this->provisioningService->provisionService($subscription);

        $this->assertInstanceOf(HostingAccount::class, $result);
        $this->assertEquals('active', $result->status);
        $this->assertEquals('example.com', $result->domain);
    }

    public function test_manage_hosting_suspend(): void
    {
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $product->id,
        ]);
        $account = HostingAccount::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
        ]);

        $this->hostingService
            ->shouldReceive('suspendAccount')
            ->once()
            ->with(Mockery::on(fn ($acc): bool => $acc->id === $account->id))
            ->andReturn(true);

        $result = $this->provisioningService->manageService($subscription, 'suspend');

        $this->assertTrue($result);
    }

    public function test_manage_hosting_unsuspend(): void
    {
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $product->id,
        ]);
        $account = HostingAccount::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'suspended',
        ]);

        $this->hostingService
            ->shouldReceive('unsuspendAccount')
            ->once()
            ->with(Mockery::on(fn ($acc): bool => $acc->id === $account->id))
            ->andReturn(true);

        $result = $this->provisioningService->manageService($subscription, 'unsuspend');

        $this->assertTrue($result);
    }

    public function test_manage_hosting_terminate(): void
    {
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $product->id,
        ]);
        $account = HostingAccount::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
        ]);

        $this->hostingService
            ->shouldReceive('terminateAccount')
            ->once()
            ->with(Mockery::on(fn ($acc): bool => $acc->id === $account->id))
            ->andReturn(true);

        $result = $this->provisioningService->manageService($subscription, 'terminate');

        $this->assertTrue($result);
    }

    public function test_manage_hosting_upgrade(): void
    {
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $newProduct = Products_Service::factory()->create([
            'type' => 'hosting',
            'name' => 'premium-plan',
        ]);
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $product->id,
        ]);
        $account = HostingAccount::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
        ]);

        $this->hostingService
            ->shouldReceive('upgradeAccount')
            ->once()
            ->with(
                Mockery::on(fn ($acc): bool => $acc->id === $account->id),
                Mockery::on(fn ($prod): bool => $prod->id === $newProduct->id),
                Mockery::any()
            )
            ->andReturn(true);

        $result = $this->provisioningService->manageService($subscription, 'upgrade', [
            'new_product' => $newProduct,
        ]);

        $this->assertTrue($result);
    }

    public function test_manage_hosting_downgrade(): void
    {
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $newProduct = Products_Service::factory()->create([
            'type' => 'hosting',
            'name' => 'basic-plan',
        ]);
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $product->id,
        ]);
        $account = HostingAccount::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
        ]);

        $this->hostingService
            ->shouldReceive('downgradeAccount')
            ->once()
            ->with(
                Mockery::on(fn ($acc): bool => $acc->id === $account->id),
                Mockery::on(fn ($prod): bool => $prod->id === $newProduct->id),
                Mockery::any()
            )
            ->andReturn(true);

        $result = $this->provisioningService->manageService($subscription, 'downgrade', [
            'new_product' => $newProduct,
        ]);

        $this->assertTrue($result);
    }

    public function test_manage_hosting_add_addon(): void
    {
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $product->id,
        ]);
        $account = HostingAccount::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
        ]);

        $this->hostingService
            ->shouldReceive('addAddon')
            ->once()
            ->with(
                Mockery::on(fn ($acc): bool => $acc->id === $account->id),
                'ssl-certificate'
            )
            ->andReturn(true);

        $result = $this->provisioningService->manageService($subscription, 'add_addon', [
            'addon' => 'ssl-certificate',
        ]);

        $this->assertTrue($result);
    }

    public function test_manage_hosting_remove_addon(): void
    {
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $subscription = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $product->id,
        ]);
        $account = HostingAccount::factory()->create([
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'addons' => ['ssl-certificate'],
        ]);

        $this->hostingService
            ->shouldReceive('removeAddon')
            ->once()
            ->with(
                Mockery::on(fn ($acc): bool => $acc->id === $account->id),
                'ssl-certificate'
            )
            ->andReturn(true);

        $result = $this->provisioningService->manageService($subscription, 'remove_addon', [
            'addon' => 'ssl-certificate',
        ]);

        $this->assertTrue($result);
    }

    public function test_provision_service_throws_exception_for_unsupported_type(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported service type');

        $product = Products_Service::factory()->create(['type' => 'unknown-type']);
        $subscription = Subscription::factory()->create([
            'product_service_id' => $product->id,
        ]);

        $this->provisioningService->provisionService($subscription);
    }

    public function test_manage_service_throws_exception_for_missing_hosting_account(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hosting account not found');

        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $subscription = Subscription::factory()->create([
            'product_service_id' => $product->id,
        ]);

        $this->provisioningService->manageService($subscription, 'suspend');
    }
}
