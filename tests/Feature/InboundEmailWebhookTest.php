<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\VerifyInboundEmailSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class InboundEmailWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Throwaway route exercising the middleware in isolation; the real
        // inbound-email route is wired elsewhere.
        Route::post('__test/inbound-email', fn () => response('ok', 200))
            ->middleware(VerifyInboundEmailSignature::class);
    }

    private function postWebhook(string $body, ?string $signature): TestResponse
    {
        $server = ['CONTENT_TYPE' => 'application/json'];

        if ($signature !== null) {
            $server['HTTP_X_WEBHOOK_SIGNATURE'] = $signature;
        }

        return $this->call('POST', '__test/inbound-email', [], [], [], $server, $body);
    }

    public function test_valid_signature_passes_through(): void
    {
        config()->set('services.inbound_email.secret', 'testsecret');
        $body = json_encode(['from' => 'jane@example.com']);

        $this->postWebhook($body, hash_hmac('sha256', $body, 'testsecret'))
            ->assertOk();
    }

    public function test_missing_signature_is_forbidden(): void
    {
        config()->set('services.inbound_email.secret', 'testsecret');

        $this->postWebhook(json_encode(['from' => 'attacker@example.com']), null)
            ->assertForbidden();
    }

    public function test_incorrect_signature_is_forbidden(): void
    {
        config()->set('services.inbound_email.secret', 'testsecret');

        $this->postWebhook(json_encode(['from' => 'attacker@example.com']), 'deadbeef')
            ->assertForbidden();
    }

    public function test_unconfigured_secret_fails_closed(): void
    {
        config()->set('services.inbound_email.secret', null);
        $body = json_encode(['from' => 'jane@example.com']);

        // Even a "correctly" signed request is rejected when no secret is set.
        $this->postWebhook($body, hash_hmac('sha256', $body, ''))
            ->assertForbidden();
    }
}
