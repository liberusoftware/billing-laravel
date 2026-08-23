<?php

namespace Tests\Unit\Services\ControlPanels;

use App\Models\HostingServer;
use App\Services\ControlPanels\LiberuControlPanelClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LiberuControlPanelClientTest extends TestCase
{
    use RefreshDatabase;

    protected $liberuClient;

    protected $guzzleClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guzzleClient = Mockery::mock(Client::class);
        $this->liberuClient = new LiberuControlPanelClient;

        // Use reflection to inject the mock Guzzle client
        $reflection = new \ReflectionClass($this->liberuClient);
        $property = $reflection->getProperty('client');
        $property->setValue($this->liberuClient, $this->guzzleClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_account(): void
    {
        $server = HostingServer::factory()->liberu()->create();
        $this->liberuClient->setServer($server);

        $responseBody = json_encode([
            'success' => true,
            'data' => ['id' => 1],
        ]);

        $this->guzzleClient
            ->shouldReceive('request')
            ->once()
            ->with('POST', Mockery::any(), Mockery::any())
            ->andReturn(new Response(201, [], $responseBody));

        $result = $this->liberuClient->createAccount('testuser', 'test.com', 'basic');

        $this->assertTrue($result);
    }

    public function test_suspend_account(): void
    {
        $server = HostingServer::factory()->liberu()->create();
        $this->liberuClient->setServer($server);

        $responseBody = json_encode([
            'success' => true,
        ]);

        $this->guzzleClient
            ->shouldReceive('request')
            ->once()
            ->with('POST', Mockery::any(), Mockery::any())
            ->andReturn(new Response(200, [], $responseBody));

        $result = $this->liberuClient->suspendAccount('testuser');

        $this->assertTrue($result);
    }

    public function test_unsuspend_account(): void
    {
        $server = HostingServer::factory()->liberu()->create();
        $this->liberuClient->setServer($server);

        $responseBody = json_encode([
            'success' => true,
        ]);

        $this->guzzleClient
            ->shouldReceive('request')
            ->once()
            ->with('POST', Mockery::any(), Mockery::any())
            ->andReturn(new Response(200, [], $responseBody));

        $result = $this->liberuClient->unsuspendAccount('testuser');

        $this->assertTrue($result);
    }

    public function test_change_package(): void
    {
        $server = HostingServer::factory()->liberu()->create();
        $this->liberuClient->setServer($server);

        $responseBody = json_encode([
            'success' => true,
        ]);

        $this->guzzleClient
            ->shouldReceive('request')
            ->once()
            ->with('PUT', Mockery::any(), Mockery::any())
            ->andReturn(new Response(200, [], $responseBody));

        $result = $this->liberuClient->changePackage('testuser', 'premium');

        $this->assertTrue($result);
    }

    public function test_terminate_account(): void
    {
        $server = HostingServer::factory()->liberu()->create();
        $this->liberuClient->setServer($server);

        $responseBody = json_encode([
            'success' => true,
        ]);

        $this->guzzleClient
            ->shouldReceive('request')
            ->once()
            ->with('DELETE', Mockery::any(), Mockery::any())
            ->andReturn(new Response(200, [], $responseBody));

        $result = $this->liberuClient->terminateAccount('testuser');

        $this->assertTrue($result);
    }

    public function test_add_addon(): void
    {
        $server = HostingServer::factory()->liberu()->create();
        $this->liberuClient->setServer($server);

        $responseBody = json_encode([
            'success' => true,
        ]);

        $this->guzzleClient
            ->shouldReceive('request')
            ->once()
            ->with('POST', Mockery::any(), Mockery::any())
            ->andReturn(new Response(200, [], $responseBody));

        $result = $this->liberuClient->addAddon('testuser', 'ssl-certificate');

        $this->assertTrue($result);
    }

    public function test_remove_addon(): void
    {
        $server = HostingServer::factory()->liberu()->create();
        $this->liberuClient->setServer($server);

        $responseBody = json_encode([
            'success' => true,
        ]);

        $this->guzzleClient
            ->shouldReceive('request')
            ->once()
            ->with('DELETE', Mockery::any(), Mockery::any())
            ->andReturn(new Response(200, [], $responseBody));

        $result = $this->liberuClient->removeAddon('testuser', 'ssl-certificate');

        $this->assertTrue($result);
    }
}
