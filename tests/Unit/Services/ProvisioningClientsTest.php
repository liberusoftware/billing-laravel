<?php

namespace Tests\Unit\Services;

use App\Models\HostingServer;
use App\Services\ControlPanels\CpanelClient;
use App\Services\ControlPanels\DirectAdminClient;
use App\Services\ControlPanels\LiberuControlPanelClient;
use App\Services\ControlPanels\PleskClient;
use App\Services\ControlPanels\SoftaculousClient;
use App\Services\ControlPanels\VirtualminClient;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Contract verification for the non-cPanel control-panel provisioning clients
 * (Plesk, DirectAdmin, Virtualmin, Liberu — plus cPanel as the reference), and
 * the standalone Softaculous installer.
 *
 * Each lifecycle client news-up its own Guzzle client in the constructor, so
 * Http::fake cannot intercept it (same constraint as CpanelClient). We instead
 * inject a Guzzle client backed by a MockHandler over the protected $client
 * property, which exercises the real request-building and response-parsing path
 * while keeping the HTTP deterministic.
 */
class ProvisioningClientsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return iterable<string, array{0: class-string, 1: string, 2: int, 3: string, 4: string, 5: string}>
     */
    public static function lifecycleClients(): iterable
    {
        // [class, successBody, failStatus, failBody, createUrlFragment, createMethod]
        yield 'cpanel' => [
            CpanelClient::class,
            '{"metadata":{"result":1}}',
            200,
            '{"metadata":{"result":0,"reason":"x"}}',
            '/json-api/createacct',
            'GET',
        ];
        yield 'plesk' => [
            PleskClient::class,
            '<packet><status>ok</status></packet>',
            200,
            '<packet><status>error</status><errtext>bad</errtext></packet>',
            '/api/v2/cli/server/',
            'POST',
        ];
        yield 'directadmin' => [
            DirectAdminClient::class,
            'error=0',
            200,
            'error=1&text=bad',
            '/CMD_API_ACCOUNT_USER',
            'POST',
        ];
        yield 'virtualmin' => [
            VirtualminClient::class,
            '{"status":"success"}',
            200,
            '{"status":"failure","error":"bad"}',
            '/virtual-server/remote.cgi',
            'POST',
        ];
        yield 'liberu' => [
            LiberuControlPanelClient::class,
            '{}',
            422,
            '{"message":"bad"}',
            '/api/hosting/accounts',
            'POST',
        ];
    }

    /**
     * @return iterable<string, array{0: class-string}>
     */
    public static function clientClasses(): iterable
    {
        foreach (self::lifecycleClients() as $name => $row) {
            yield $name => [$row[0]];
        }
    }

    /**
     * Inject a Guzzle client backed by the given queued responses into the
     * client's protected $client property, capturing sent requests in $history.
     *
     * @param  array<int, Response>  $responses
     * @param  array<int, array>  $history
     */
    private function injectGuzzle(object $client, array $responses, array &$history): void
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        $property = new ReflectionProperty($client, 'client');
        $property->setValue($client, new Client(['handler' => $stack]));
    }

    #[DataProvider('clientClasses')]
    public function test_client_implements_lifecycle_contract(string $class): void
    {
        foreach (['setServer', 'createAccount', 'suspendAccount', 'unsuspendAccount', 'changePackage', 'terminateAccount', 'addAddon', 'removeAddon'] as $method) {
            $this->assertTrue(method_exists($class, $method), "{$class} is missing {$method}()");
        }
    }

    #[DataProvider('lifecycleClients')]
    public function test_create_account_makes_expected_call_and_returns_true(string $class, string $successBody, int $failStatus, string $failBody, string $urlFragment, string $method): void
    {
        // Public IP-literal hostname so CpanelClient::validateHostname() stays
        // deterministic (no DNS lookup of a random fake domain).
        $server = HostingServer::factory()->create(['hostname' => '203.0.113.10']);
        $client = new $class;
        $client->setServer($server);

        $history = [];
        $this->injectGuzzle($client, [new Response(200, [], $successBody)], $history);

        $this->assertTrue($client->createAccount('testuser', 'example.com', 'basic'));

        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertSame($method, $request->getMethod());
        $this->assertStringContainsString($urlFragment, (string) $request->getUri());
    }

    #[DataProvider('lifecycleClients')]
    public function test_failure_response_returns_false_without_throwing(string $class, string $successBody, int $failStatus, string $failBody, string $urlFragment, string $method): void
    {
        $server = HostingServer::factory()->create(['hostname' => '203.0.113.10']);
        $client = new $class;
        $client->setServer($server);

        $history = [];
        $this->injectGuzzle($client, [new Response($failStatus, [], $failBody)], $history);

        $this->assertFalse($client->suspendAccount('testuser'));
    }

    #[DataProvider('clientClasses')]
    public function test_method_throws_when_server_not_configured(string $class): void
    {
        $client = new $class;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Server not configured');

        $client->suspendAccount('testuser');
    }

    /**
     * The Plesk webspace.add request must be well-formed XML with repeated
     * <property>/<limit> elements — not invalid <0>/<1> numeric tags.
     */
    public function test_plesk_create_request_is_well_formed_xml(): void
    {
        $server = HostingServer::factory()->create();
        $client = new PleskClient;
        $client->setServer($server);

        $history = [];
        $this->injectGuzzle($client, [new Response(200, [], '<packet><status>ok</status></packet>')], $history);

        $client->createAccount('testuser', 'example.com', 'basic');

        $body = (string) $history[0]['request']->getBody();
        $this->assertNotFalse(simplexml_load_string($body), 'Plesk request body is not valid XML');
        $this->assertStringContainsString('<property>', $body);
        $this->assertStringNotContainsString('<0', $body);
    }

    public function test_softaculous_install_script_returns_true_on_success(): void
    {
        $client = new SoftaculousClient;

        $history = [];
        $this->injectGuzzle($client, [new Response(200, [], '{"success":true}')], $history);

        $this->assertTrue($client->installScript('example.com', 26));
        $this->assertStringContainsString('/install', (string) $history[0]['request']->getUri());
    }

    public function test_softaculous_install_script_returns_false_on_failure(): void
    {
        $client = new SoftaculousClient;

        $history = [];
        $this->injectGuzzle($client, [new Response(200, [], '{"success":false}')], $history);

        $this->assertFalse($client->installScript('example.com', 26));
    }
}
