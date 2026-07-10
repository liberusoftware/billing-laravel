<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tld;
use App\Services\Registrars\EnomClient;
use App\Services\Registrars\ResellerClubClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * Security regressions for the domain-registrar clients:
 * M2 caller-param credential override, M3 credential leak via error/exception messages,
 * and H2 availability caching that caps registrar blast radius.
 */
class DomainRegistrarSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function queryOf(object $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * M2 (ResellerClub): a caller-supplied param must not override the trusted
     * auth-userid / api-key merged by makeApiCall.
     */
    public function test_resellerclub_caller_param_cannot_override_credentials(): void
    {
        config([
            'services.resellerclub.api_url' => 'https://httpapi.example.com/api',
            'services.resellerclub.auth_userid' => 'trusted-uid',
            'services.resellerclub.api_key' => 'trusted-key',
        ]);
        Http::fake(['*' => Http::response(['status' => 'ok'])]);

        $client = app(ResellerClubClient::class);
        (new ReflectionMethod($client, 'makeApiCall'))->invoke($client, 'domains/available.json', [
            'auth-userid' => 'attacker',
            'api-key' => 'attacker-key',
            'domain-name' => 'evil',
        ]);

        Http::assertSent(function ($request): bool {
            $query = $this->queryOf($request);

            return $query['auth-userid'] === 'trusted-uid'
                && $query['api-key'] === 'trusted-key'
                && $query['domain-name'] === 'evil';
        });
    }

    /**
     * M3 (eNom): a connection failure must surface a sanitized message that does not
     * leak the password or the raw registrar URL.
     */
    public function test_enom_connection_failure_message_is_sanitized(): void
    {
        config([
            'services.enom.api_url' => 'https://reseller.enom.com/interface.asp',
            'services.enom.username' => 'trusted-uid',
            'services.enom.password' => 'SUPERSECRETPW',
        ]);
        Http::fake(['*' => fn () => throw new ConnectionException(
            'cURL error 7: Failed to connect (https://reseller.enom.com/interface.asp?command=Check&uid=trusted-uid&pw=SUPERSECRETPW)'
        )]);

        try {
            app(EnomClient::class)->checkAvailability('foo.com');
            $this->fail('Expected a sanitized RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('SUPERSECRETPW', $e->getMessage());
            $this->assertStringNotContainsString('reseller.enom.com', $e->getMessage());
        }
    }

    /**
     * M3 (ResellerClub): a connection failure must not leak the api-key or raw URL.
     */
    public function test_resellerclub_connection_failure_message_is_sanitized(): void
    {
        config([
            'services.resellerclub.api_url' => 'https://httpapi.example.com/api',
            'services.resellerclub.auth_userid' => 'trusted-uid',
            'services.resellerclub.api_key' => 'SUPERSECRETKEY',
        ]);
        Http::fake(['*' => fn () => throw new ConnectionException(
            'cURL error 7: Failed to connect (https://httpapi.example.com/api/domains/available.json?api-key=SUPERSECRETKEY)'
        )]);

        try {
            app(ResellerClubClient::class)->checkAvailability('foo.com');
            $this->fail('Expected a sanitized RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('SUPERSECRETKEY', $e->getMessage());
            $this->assertStringNotContainsString('httpapi.example.com', $e->getMessage());
        }
    }

    /**
     * M3 (ResellerClub): an HTTP error must not echo the raw response body to the caller.
     */
    public function test_resellerclub_http_error_does_not_leak_response_body(): void
    {
        Http::fake(['*' => Http::response('RAW-BODY-LEAKING-INTERNAL-SECRET', 500)]);

        try {
            app(ResellerClubClient::class)->checkAvailability('boom.com');
            $this->fail('Expected a RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('RAW-BODY-LEAKING-INTERNAL-SECRET', $e->getMessage());
        }
    }

    /**
     * H2: repeated availability lookups for the same domain must be served from cache
     * within the window, so a looped/replayed search does not re-hit the registrar.
     */
    public function test_domain_availability_is_cached_across_requests(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount><RRPCode>210</RRPCode></interface-response>'),
        ]);

        Tld::create([
            'name' => '.com',
            'enom_cost' => 11,
            'base_price' => 11,
            'markup_type' => 'percentage',
            'markup_value' => 10,
        ]);

        $this->getJson('/domains/search?domain=foo.com')->assertOk();
        // First request fans out to: foo.com (primary) + foo.net + foo.org = 3 registrar calls.
        $this->getJson('/domains/search?domain=foo.com')->assertOk();

        // Second identical request is fully cached: no additional registrar calls.
        Http::assertSentCount(3);
    }
}
