<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\VoipPlatform;
use App\Models\DidNumber;
use App\Models\VoipAccount;
use App\Services\Voip\VoipPlatformClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VoipPlatformClientsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{VoipPlatform}> */
    public static function platforms(): array
    {
        return [
            'Asterisk' => [VoipPlatform::Asterisk],
            'FreePBX' => [VoipPlatform::FreePbx],
            'FusionPBX' => [VoipPlatform::FusionPbx],
            '3CX' => [VoipPlatform::ThreeCx],
        ];
    }

    #[DataProvider('platforms')]
    public function test_platform_provisions_sip_account_and_assigned_dids(VoipPlatform $platform): void
    {
        config([
            "voip.platforms.{$platform->value}.url" => "https://{$platform->value}.example.test/api",
            "voip.platforms.{$platform->value}.token" => 'trusted-token',
        ]);
        Http::fake(['*' => Http::response([], 201)]);
        $account = VoipAccount::factory()->create([
            'platform' => $platform,
            'sip_username' => 'sip-user',
            'sip_secret' => 'sip-password-value',
        ]);
        DidNumber::query()->create([
            'team_id' => $account->team_id,
            'voip_account_id' => $account->id,
            'number' => '+15551234567',
            'country_code' => 'US',
            'status' => 'assigned',
        ]);

        app(VoipPlatformClientFactory::class)->make($platform)->provisionAccount($account);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/sip/accounts')
            && $request->hasHeader('Authorization', 'Bearer trusted-token')
            && $request['secret'] === 'sip-password-value'
            && $request['did_numbers'] === ['+15551234567']);
    }
}
