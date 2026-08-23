<?php

namespace Tests\Unit\Services;

use App\Models\HostingAccount;
use App\Models\HostingServer;
use App\Models\Products_Service;
use App\Services\ControlPanels\CpanelClient;
use App\Services\ControlPanels\DirectAdminClient;
use App\Services\ControlPanels\LiberuControlPanelClient;
use App\Services\ControlPanels\PleskClient;
use App\Services\ControlPanels\VirtualminClient;
use App\Services\HostingService;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class HostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $hostingService;

    protected $cpanelClient;

    protected $pleskClient;

    protected $directAdminClient;

    protected $virtualminClient;

    protected $liberuClient;

    protected $pricingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock clients
        $this->cpanelClient = Mockery::mock(CpanelClient::class);
        $this->pleskClient = Mockery::mock(PleskClient::class);
        $this->directAdminClient = Mockery::mock(DirectAdminClient::class);
        $this->virtualminClient = Mockery::mock(VirtualminClient::class);
        $this->liberuClient = Mockery::mock(LiberuControlPanelClient::class);
        $this->pricingService = Mockery::mock(PricingService::class);

        $this->hostingService = new HostingService(
            $this->cpanelClient,
            $this->pleskClient,
            $this->directAdminClient,
            $this->virtualminClient,
            $this->liberuClient,
            $this->pricingService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_provision_account_with_cpanel(): void
    {
        $server = HostingServer::factory()->cpanel()->create();
        $account = HostingAccount::factory()->make([
            'status' => 'pending',
            'hosting_server_id' => null,
        ]);
        $account->save();

        $product = Products_Service::factory()->create(['name' => 'basic-plan']);

        $this->cpanelClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        $this->cpanelClient
            ->shouldReceive('createAccount')
            ->once()
            ->with($account->username, $account->domain, $product->name)
            ->andReturn(true);

        $this->pricingService
            ->shouldReceive('calculatePrice')
            ->once()
            ->andReturn(19.99);

        $result = $this->hostingService->provisionAccount($account, $product);

        $this->assertTrue($result);
        $this->assertEquals('active', $account->fresh()->status);
        $this->assertEquals($server->id, $account->fresh()->hosting_server_id);
    }

    public function test_provision_account_with_liberu(): void
    {
        $server = HostingServer::factory()->liberu()->create();
        $account = HostingAccount::factory()->make([
            'status' => 'pending',
            'hosting_server_id' => null,
        ]);
        $account->save();

        $product = Products_Service::factory()->create(['name' => 'premium-plan']);

        $this->liberuClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        $this->liberuClient
            ->shouldReceive('createAccount')
            ->once()
            ->with($account->username, $account->domain, $product->name)
            ->andReturn(true);

        $this->pricingService
            ->shouldReceive('calculatePrice')
            ->once()
            ->andReturn(49.99);

        $result = $this->hostingService->provisionAccount($account, $product);

        $this->assertTrue($result);
        $this->assertEquals('active', $account->fresh()->status);
    }

    public function test_suspend_account(): void
    {
        $server = HostingServer::factory()->cpanel()->create();
        $account = HostingAccount::factory()->create([
            'hosting_server_id' => $server->id,
            'status' => 'active',
        ]);

        $this->cpanelClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        $this->cpanelClient
            ->shouldReceive('suspendAccount')
            ->once()
            ->with($account->username)
            ->andReturn(true);

        $result = $this->hostingService->suspendAccount($account);

        $this->assertTrue($result);
        $this->assertEquals('suspended', $account->fresh()->status);
    }

    public function test_unsuspend_account(): void
    {
        $server = HostingServer::factory()->plesk()->create();
        $account = HostingAccount::factory()->create([
            'hosting_server_id' => $server->id,
            'status' => 'suspended',
        ]);

        $this->pleskClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        $this->pleskClient
            ->shouldReceive('unsuspendAccount')
            ->once()
            ->with($account->username)
            ->andReturn(true);

        $result = $this->hostingService->unsuspendAccount($account);

        $this->assertTrue($result);
        $this->assertEquals('active', $account->fresh()->status);
    }

    public function test_upgrade_account(): void
    {
        $server = HostingServer::factory()->directadmin()->create();
        $account = HostingAccount::factory()->create([
            'hosting_server_id' => $server->id,
            'package' => 'basic-plan',
            'price' => 10.00,
        ]);

        $newProduct = Products_Service::factory()->create(['name' => 'premium-plan']);

        $this->directAdminClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        $this->directAdminClient
            ->shouldReceive('changePackage')
            ->once()
            ->with($account->username, 'premium-plan')
            ->andReturn(true);

        $this->pricingService
            ->shouldReceive('calculatePrice')
            ->once()
            ->andReturn(29.99);

        $result = $this->hostingService->upgradeAccount($account, $newProduct);

        $this->assertTrue($result);
        $this->assertEquals('premium-plan', $account->fresh()->package);
        $this->assertEquals(29.99, $account->fresh()->price);
    }

    public function test_downgrade_account(): void
    {
        $server = HostingServer::factory()->virtualmin()->create();
        $account = HostingAccount::factory()->create([
            'hosting_server_id' => $server->id,
            'package' => 'premium-plan',
            'price' => 30.00,
        ]);

        $newProduct = Products_Service::factory()->create(['name' => 'basic-plan']);

        $this->virtualminClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        $this->virtualminClient
            ->shouldReceive('changePackage')
            ->once()
            ->with($account->username, 'basic-plan')
            ->andReturn(true);

        $this->pricingService
            ->shouldReceive('calculatePrice')
            ->once()
            ->andReturn(9.99);

        $result = $this->hostingService->downgradeAccount($account, $newProduct);

        $this->assertTrue($result);
        $this->assertEquals('basic-plan', $account->fresh()->package);
        $this->assertEquals(9.99, $account->fresh()->price);
    }

    public function test_terminate_account(): void
    {
        $server = HostingServer::factory()->cpanel()->create([
            'active_accounts' => 10,
        ]);
        $account = HostingAccount::factory()->create([
            'hosting_server_id' => $server->id,
            'status' => 'active',
        ]);

        $this->cpanelClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        $this->cpanelClient
            ->shouldReceive('terminateAccount')
            ->once()
            ->with($account->username)
            ->andReturn(true);

        $result = $this->hostingService->terminateAccount($account);

        $this->assertTrue($result);
        $this->assertEquals('terminated', $account->fresh()->status);
        $this->assertEquals(9, $server->fresh()->active_accounts);
    }

    public function test_add_addon(): void
    {
        $server = HostingServer::factory()->cpanel()->create();
        $account = HostingAccount::factory()->create([
            'hosting_server_id' => $server->id,
            'addons' => [],
        ]);

        $this->cpanelClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        $this->cpanelClient
            ->shouldReceive('addAddon')
            ->once()
            ->with($account->username, 'ssl-certificate')
            ->andReturn(true);

        $result = $this->hostingService->addAddon($account, 'ssl-certificate');

        $this->assertTrue($result);
        $this->assertTrue($account->fresh()->hasAddon('ssl-certificate'));
    }

    public function test_remove_addon(): void
    {
        $server = HostingServer::factory()->liberu()->create();
        $account = HostingAccount::factory()->create([
            'hosting_server_id' => $server->id,
            'addons' => ['ssl-certificate', 'backup-service'],
        ]);

        $this->liberuClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $server->id));

        $this->liberuClient
            ->shouldReceive('removeAddon')
            ->once()
            ->with($account->username, 'backup-service')
            ->andReturn(true);

        $result = $this->hostingService->removeAddon($account, 'backup-service');

        $this->assertTrue($result);
        $this->assertFalse($account->fresh()->hasAddon('backup-service'));
        $this->assertTrue($account->fresh()->hasAddon('ssl-certificate'));
    }

    public function test_provision_account_selects_available_server(): void
    {
        // Create multiple servers, one at capacity
        HostingServer::factory()->cpanel()->atCapacity()->create();
        $availableServer = HostingServer::factory()->cpanel()->create([
            'active_accounts' => 5,
            'max_accounts' => 100,
        ]);

        $account = HostingAccount::factory()->make([
            'status' => 'pending',
            'hosting_server_id' => null,
        ]);
        $account->save();

        $product = Products_Service::factory()->create(['name' => 'basic-plan']);

        $this->cpanelClient
            ->shouldReceive('setServer')
            ->once()
            ->with(Mockery::on(fn ($s): bool => $s->id === $availableServer->id));

        $this->cpanelClient
            ->shouldReceive('createAccount')
            ->once()
            ->andReturn(true);

        $this->pricingService
            ->shouldReceive('calculatePrice')
            ->once()
            ->andReturn(19.99);

        $result = $this->hostingService->provisionAccount($account, $product);

        $this->assertTrue($result);
        $this->assertEquals($availableServer->id, $account->fresh()->hosting_server_id);
        $this->assertEquals(6, $availableServer->fresh()->active_accounts);
    }
}
