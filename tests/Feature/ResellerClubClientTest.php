<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Registrars\ResellerClubClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ResellerClubClientTest extends TestCase
{
    public function test_availability_parses_available_response(): void
    {
        Http::fake([
            '*' => Http::response(['example.com' => ['status' => 'available']]),
        ]);

        $this->assertTrue(app(ResellerClubClient::class)->checkAvailability('example.com'));
    }

    public function test_taken_domain_returns_false(): void
    {
        Http::fake([
            '*' => Http::response(['taken.com' => ['status' => 'regthroughothers']]),
        ]);

        $this->assertFalse(app(ResellerClubClient::class)->checkAvailability('taken.com'));
    }

    public function test_resellerclub_register_calls_api(): void
    {
        config([
            'services.resellerclub.auth_userid' => 'reseller1',
            'services.resellerclub.api_key' => 'secret-key',
        ]);
        Http::fake([
            '*' => Http::response(['entityid' => '123', 'status' => 'Success']),
        ]);

        app(ResellerClubClient::class)->registerDomain('example.com', 1);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'domains/register.json')
                && str_contains($request->url(), 'auth-userid=reseller1')
                && str_contains($request->url(), 'api-key=secret-key');
        });
    }

    public function test_api_error_throws(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'ERROR', 'message' => 'Invalid credentials'], 400),
        ]);

        $this->expectException(RuntimeException::class);
        app(ResellerClubClient::class)->checkAvailability('boom.com');
    }
}
