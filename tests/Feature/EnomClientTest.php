<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Registrars\EnomClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class EnomClientTest extends TestCase
{
    public function test_availability_parses_available_response(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount><RRPCode>210</RRPCode></interface-response>'),
        ]);

        $this->assertTrue(app(EnomClient::class)->checkAvailability('free.com'));
    }

    public function test_taken_domain_returns_false(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount><RRPCode>211</RRPCode></interface-response>'),
        ]);

        $this->assertFalse(app(EnomClient::class)->checkAvailability('taken.com'));
    }

    public function test_register_calls_enom_with_credentials(): void
    {
        config(['services.enom.username' => 'reseller1', 'services.enom.password' => 'secret']);
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount><RRPCode>200</RRPCode></interface-response>'),
        ]);

        app(EnomClient::class)->registerDomain('example.com', 42);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'command=Purchase')
                && str_contains($request->url(), 'SLD=example')
                && str_contains($request->url(), 'TLD=com')
                && str_contains($request->url(), 'uid=reseller1');
        });
    }

    public function test_api_error_throws(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>1</ErrCount><errors><Err1>Domain unavailable</Err1></errors></interface-response>'),
        ]);

        $this->expectException(RuntimeException::class);
        app(EnomClient::class)->checkAvailability('boom.com');
    }
}
