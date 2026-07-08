<?php

namespace Tests\Unit\Services;

use App\Models\HostingServer;
use App\Services\ControlPanels\CpanelClient;
use App\Services\Registrars\EnomClient;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Security regression tests for the external API clients:
 * D1 eNom parameter injection, D2 cPanel SSRF via DNS, D3 SSO open redirect.
 *
 * These clients news-up their own Guzzle client, so we inject a MockHandler-backed
 * client over the protected $client property (same pattern as ProvisioningClientsTest).
 *
 * @param  array<int, Response>  $responses
 * @param  array<int, array>  $history
 */
class IntegrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, Response>  $responses
     * @param  array<int, array<string, mixed>>  $history
     */
    private function injectGuzzle(object $client, array $responses, array &$history): void
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        (new ReflectionProperty($client, 'client'))->setValue($client, new Client(['handler' => $stack]));
    }

    /**
     * D1: a DNS record carrying command/uid/pw keys must NOT override the trusted
     * command and credentials in the outgoing eNom request.
     */
    public function test_enom_record_cannot_override_command_or_credentials(): void
    {
        config([
            'services.enom.api_url' => 'https://reseller.enom.com/interface.asp',
            'services.enom.username' => 'trusted-uid',
            'services.enom.password' => 'trusted-pw',
        ]);

        $client = new EnomClient;

        $history = [];
        $this->injectGuzzle($client, [new Response(200, [], '<interface-response></interface-response>')], $history);

        $client->addDnsRecord('example.com', [
            'command' => 'DeleteAllDomains',
            'uid' => 'attacker',
            'pw' => 'attacker-pw',
            'SLD' => 'evil',
            'type' => 'A',
            'name' => 'www',
            'content' => '203.0.113.5',
        ]);

        $this->assertCount(1, $history);
        parse_str((string) $history[0]['request']->getUri()->getQuery(), $query);

        $this->assertSame('SetHosts', $query['command']);
        $this->assertSame('trusted-uid', $query['uid']);
        $this->assertSame('trusted-pw', $query['pw']);
        $this->assertSame('example.com', $query['SLD']);
        // Whitelisted record fields still pass through.
        $this->assertSame('A', $query['type']);
        $this->assertSame('www', $query['name']);
    }

    /**
     * D2: a public-looking hostname that resolves to a private/reserved IP must be
     * rejected, not just literal private IPs. localhost resolves to 127.0.0.1.
     */
    public function test_cpanel_rejects_hostname_resolving_to_private_ip(): void
    {
        $server = HostingServer::factory()->cpanel()->create(['hostname' => 'localhost']);
        $client = new CpanelClient;
        $client->setServer($server);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('resolves to a private or reserved address');

        $client->createAccount('testuser', 'example.com', 'basic');
    }

    /**
     * D2 (pin): the outgoing WHM request must pin DNS to the validated IP via
     * CURLOPT_RESOLVE, closing the rebind TOCTOU between validate and connect.
     * (Actual rebind-between-resolve-and-connect is not deterministically testable;
     * the MockHandler ignores the curl option, so we assert it on the request options.)
     */
    public function test_cpanel_request_pins_dns_to_validated_ip(): void
    {
        $server = HostingServer::factory()->cpanel()->create(['hostname' => '203.0.113.10']);
        $client = new CpanelClient;
        $client->setServer($server);

        $history = [];
        $this->injectGuzzle($client, [new Response(200, [], '{"metadata":{"result":1}}')], $history);

        $client->createAccount('testuser', 'example.com', 'basic');

        $this->assertCount(1, $history);
        $this->assertSame(
            ['203.0.113.10:2087:203.0.113.10'],
            $history[0]['options']['curl'][CURLOPT_RESOLVE] ?? null
        );
    }

    /**
     * D3: createSsoSession must return null when the WHM response URL host does not
     * match the configured server hostname (open-redirect guard).
     */
    public function test_cpanel_sso_returns_null_when_url_host_mismatches_server(): void
    {
        $server = HostingServer::factory()->cpanel()->create(['hostname' => '203.0.113.10']);
        $client = new CpanelClient;
        $client->setServer($server);

        $history = [];
        $this->injectGuzzle(
            $client,
            [new Response(200, [], '{"metadata":{"result":1},"data":{"url":"https://evil.example.com/login/?session=abc"}}')],
            $history
        );

        $this->assertNull($client->createSsoSession('bob'));
    }
}
