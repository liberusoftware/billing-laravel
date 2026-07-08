<?php

namespace Tests\Feature;

use App\Models\PaymentGateway;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptExistingSecretsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_encrypts_pre_existing_plaintext_secrets(): void
    {
        // Seed plaintext rows directly, bypassing the encrypted cast.
        $gatewayId = DB::table('payment_gateways')->insertGetId([
            'name' => 'Legacy Gateway',
            'api_key' => 'plain-api-key',
            'secret_key' => 'plain-secret-key',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $endpointId = DB::table('webhook_endpoints')->insertGetId([
            'url' => 'https://example.test/hook',
            'secret' => 'plain-webhook-secret',
            'is_active' => true,
            'max_retries' => 3,
            'retry_interval' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('secrets:encrypt-existing')->assertSuccessful();

        // Raw column is now ciphertext...
        $this->assertNotSame('plain-api-key', DB::table('payment_gateways')->where('id', $gatewayId)->value('api_key'));

        // ...and the model cast decrypts it back to the original plaintext.
        $gateway = PaymentGateway::find($gatewayId);
        $this->assertSame('plain-api-key', $gateway->api_key);
        $this->assertSame('plain-secret-key', $gateway->secret_key);

        $endpoint = WebhookEndpoint::find($endpointId);
        $this->assertSame('plain-webhook-secret', $endpoint->secret);
    }

    public function test_it_is_idempotent(): void
    {
        $gatewayId = DB::table('payment_gateways')->insertGetId([
            'name' => 'Legacy Gateway',
            'api_key' => 'plain-api-key',
            'secret_key' => 'plain-secret-key',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('secrets:encrypt-existing')->assertSuccessful();
        $cipherAfterFirstRun = DB::table('payment_gateways')->where('id', $gatewayId)->value('api_key');

        $this->artisan('secrets:encrypt-existing')->assertSuccessful();
        $cipherAfterSecondRun = DB::table('payment_gateways')->where('id', $gatewayId)->value('api_key');

        // Second run must not re-encrypt an already-encrypted value.
        $this->assertSame($cipherAfterFirstRun, $cipherAfterSecondRun);
        $this->assertSame('plain-api-key', Crypt::decryptString($cipherAfterSecondRun));
    }

    public function test_dry_run_does_not_write(): void
    {
        $gatewayId = DB::table('payment_gateways')->insertGetId([
            'name' => 'Legacy Gateway',
            'api_key' => 'plain-api-key',
            'secret_key' => 'plain-secret-key',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('secrets:encrypt-existing', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame('plain-api-key', DB::table('payment_gateways')->where('id', $gatewayId)->value('api_key'));
    }
}
