<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\HostingAccount;
use App\Models\HostingServer;
use App\Models\Products_Service;
use App\Models\Subscription;
use App\Services\ControlPanels\CpanelClient;
use App\Services\ControlPanels\DirectAdminClient;
use App\Services\ControlPanels\LiberuControlPanelClient;
use App\Services\ControlPanels\PleskClient;
use App\Services\ControlPanels\VirtualminClient;
use App\Services\HostingService;
use App\Services\PricingService;
use App\Services\ServiceProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * End-to-end verification of the cPanel hosting lifecycle driven through the
 * public ServiceProvisioningService entry points (provisionService/manageService),
 * exercising the real HostingService (server selection, status transitions,
 * server account counters) with only the leaf cPanel API client and pricing mocked.
 *
 * The external HTTP is made deterministic by mocking the injected CpanelClient.
 * Http::fake cannot be used here because CpanelClient news-up its own Guzzle
 * client in its constructor (see test report / CpanelClient::__construct).
 */
class ServiceProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected CpanelClient $cpanelClient;

    protected PricingService $pricingService;

    protected ServiceProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cpanelClient = Mockery::mock(CpanelClient::class);
        $this->pricingService = Mockery::mock(PricingService::class);

        $hostingService = new HostingService(
            $this->cpanelClient,
            Mockery::mock(PleskClient::class),
            Mockery::mock(DirectAdminClient::class),
            Mockery::mock(VirtualminClient::class),
            Mockery::mock(LiberuControlPanelClient::class),
            $this->pricingService,
        );

        $this->service = new ServiceProvisioningService($hostingService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function hostingSubscription(Customer $customer, Products_Service $product, ?string $domain = null): Subscription
    {
        return Subscription::factory()->create([
            'customer_id' => $customer->id,
            'product_service_id' => $product->id,
            'domain' => $domain,
        ]);
    }

    public function test_provision_creates_active_cpanel_account_and_increments_server(): void
    {
        $server = HostingServer::factory()->cpanel()->create([
            'active_accounts' => 5,
            'max_accounts' => 100,
        ]);
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create([
            'type' => 'hosting',
            'name' => 'basic-hosting',
        ]);
        $subscription = $this->hostingSubscription($customer, $product, 'example.com');

        $this->pricingService
            ->shouldReceive('calculatePrice')
            ->once()
            ->andReturn(19.99);

        $this->cpanelClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        // The right cPanel API call: createAccount with the subscription's
        // domain and the product name as the package.
        $this->cpanelClient
            ->shouldReceive('createAccount')
            ->once()
            ->with(Mockery::type('string'), 'example.com', 'basic-hosting')
            ->andReturn(true);

        $account = $this->service->provisionService($subscription);

        $this->assertInstanceOf(HostingAccount::class, $account);
        $this->assertSame('active', $account->fresh()->status);
        $this->assertSame($server->id, $account->fresh()->hosting_server_id);
        $this->assertSame('example.com', $account->domain);
        $this->assertSame(6, $server->fresh()->active_accounts);
    }

    public function test_failed_provision_marks_account_failed(): void
    {
        HostingServer::factory()->cpanel()->create([
            'active_accounts' => 0,
            'max_accounts' => 100,
        ]);
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create([
            'type' => 'hosting',
            'name' => 'basic-hosting',
        ]);
        $subscription = $this->hostingSubscription($customer, $product, 'fail.com');

        $this->pricingService
            ->shouldReceive('calculatePrice')
            ->once()
            ->andReturn(19.99);

        $this->cpanelClient->shouldReceive('setServer')->once();

        // Control panel rejects the request without throwing.
        $this->cpanelClient
            ->shouldReceive('createAccount')
            ->once()
            ->andReturn(false);

        $account = $this->service->provisionService($subscription);

        $this->assertSame('failed', $account->fresh()->status);
    }

    public function test_suspend_transitions_active_account_to_suspended(): void
    {
        $server = HostingServer::factory()->cpanel()->create();
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $subscription = $this->hostingSubscription($customer, $product);
        $account = HostingAccount::factory()->cpanel()->create([
            'subscription_id' => $subscription->id,
            'hosting_server_id' => $server->id,
            'status' => 'active',
        ]);

        $this->cpanelClient->shouldReceive('setServer')->once();
        $this->cpanelClient
            ->shouldReceive('suspendAccount')
            ->once()
            ->with($account->username)
            ->andReturn(true);

        $result = $this->service->manageService($subscription, 'suspend');

        $this->assertTrue($result);
        $this->assertSame('suspended', $account->fresh()->status);
    }

    public function test_unsuspend_transitions_suspended_account_to_active(): void
    {
        $server = HostingServer::factory()->cpanel()->create();
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $subscription = $this->hostingSubscription($customer, $product);
        $account = HostingAccount::factory()->cpanel()->create([
            'subscription_id' => $subscription->id,
            'hosting_server_id' => $server->id,
            'status' => 'suspended',
        ]);

        $this->cpanelClient->shouldReceive('setServer')->once();
        $this->cpanelClient
            ->shouldReceive('unsuspendAccount')
            ->once()
            ->with($account->username)
            ->andReturn(true);

        $result = $this->service->manageService($subscription, 'unsuspend');

        $this->assertTrue($result);
        $this->assertSame('active', $account->fresh()->status);
    }

    public function test_terminate_transitions_to_terminated_and_decrements_server(): void
    {
        $server = HostingServer::factory()->cpanel()->create([
            'active_accounts' => 10,
        ]);
        $customer = Customer::factory()->create();
        $product = Products_Service::factory()->create(['type' => 'hosting']);
        $subscription = $this->hostingSubscription($customer, $product);
        $account = HostingAccount::factory()->cpanel()->create([
            'subscription_id' => $subscription->id,
            'hosting_server_id' => $server->id,
            'status' => 'active',
        ]);

        $this->cpanelClient->shouldReceive('setServer')->once();
        $this->cpanelClient
            ->shouldReceive('terminateAccount')
            ->once()
            ->with($account->username)
            ->andReturn(true);

        $result = $this->service->manageService($subscription, 'terminate');

        $this->assertTrue($result);
        $this->assertSame('terminated', $account->fresh()->status);
        $this->assertSame(9, $server->fresh()->active_accounts);
    }
}
