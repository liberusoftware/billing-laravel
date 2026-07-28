<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\RadiusPlatform;
use App\Models\IspService;
use App\Services\Radius\RadiusClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RadiusClientsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{RadiusPlatform}>
     */
    public static function platforms(): array
    {
        return [
            'FreeRADIUS' => [RadiusPlatform::FreeRadius],
            'MikroTik RADIUS' => [RadiusPlatform::MikroTik],
            'Cisco RADIUS' => [RadiusPlatform::Cisco],
        ];
    }

    #[DataProvider('platforms')]
    public function test_platform_client_synchronizes_limits_and_credentials(RadiusPlatform $platform): void
    {
        config([
            "radius.platforms.{$platform->value}.url" => "https://{$platform->value}.example.test/api",
            "radius.platforms.{$platform->value}.token" => 'trusted-token',
        ]);
        Http::fake(['*' => Http::response([], 204)]);
        $service = IspService::factory()->create([
            'radius_platform' => $platform,
            'radius_username' => 'subscriber',
            'radius_secret' => 'radius-password',
            'download_limit_bps' => 50_000_000,
            'upload_limit_bps' => 10_000_000,
        ]);

        app(RadiusClientFactory::class)->make($platform)->synchronizeUser($service);

        Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
            && $request->url() === "https://{$platform->value}.example.test/api/users/subscriber"
            && $request->hasHeader('Authorization', 'Bearer trusted-token')
            && $request['secret'] === 'radius-password'
            && $request['download_limit_bps'] === 50_000_000);
    }

    public function test_suspend_disables_and_disconnects_radius_user(): void
    {
        config([
            'radius.platforms.freeradius.url' => 'https://radius.example.test',
            'radius.platforms.freeradius.token' => 'trusted-token',
        ]);
        Http::fake(['*' => Http::response([], 204)]);
        $service = IspService::factory()->create([
            'radius_platform' => RadiusPlatform::FreeRadius,
            'radius_username' => 'subscriber',
        ]);
        $client = app(RadiusClientFactory::class)->make(RadiusPlatform::FreeRadius);

        $client->suspendUser($service);
        $client->disconnectUser($service);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/users/subscriber')
            && $request['enabled'] === false);
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/sessions/disconnect')
            && $request['username'] === 'subscriber');
    }
}
