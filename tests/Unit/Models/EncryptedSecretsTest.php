<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EncryptedSecretsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function payment_gateway_secrets_are_encrypted_at_rest(): void
    {
        $gateway = PaymentGateway::create([
            'name' => 'Stripe',
            'api_key' => 'pk_live_plaintext',
            'secret_key' => 'sk_live_plaintext',
        ]);

        // Accessor returns plaintext.
        $this->assertSame('pk_live_plaintext', $gateway->fresh()->api_key);
        $this->assertSame('sk_live_plaintext', $gateway->fresh()->secret_key);

        // Raw DB value is ciphertext, not the plaintext.
        $row = DB::table('payment_gateways')->where('id', $gateway->id)->first();
        $this->assertNotSame('pk_live_plaintext', $row->api_key);
        $this->assertNotSame('sk_live_plaintext', $row->secret_key);
    }

    #[Test]
    public function payment_method_token_is_encrypted_at_rest(): void
    {
        $method = PaymentMethod::create([
            'customer_id' => Customer::factory()->create()->id,
            'type' => 'card',
            'token' => 'tok_plaintext',
        ]);

        $this->assertSame('tok_plaintext', $method->fresh()->token);

        $raw = DB::table('payment_methods')->where('id', $method->id)->value('token');
        $this->assertNotSame('tok_plaintext', $raw);
    }

    #[Test]
    public function webhook_endpoint_secret_is_encrypted_at_rest(): void
    {
        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_plaintext',
        ]);

        $this->assertSame('whsec_plaintext', $endpoint->fresh()->secret);

        $raw = DB::table('webhook_endpoints')->where('id', $endpoint->id)->value('secret');
        $this->assertNotSame('whsec_plaintext', $raw);
    }
}
