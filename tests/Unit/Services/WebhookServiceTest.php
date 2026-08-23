<?php

namespace Tests\Unit\Services;

use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WebhookService $webhookService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->webhookService = new WebhookService;
    }

    public function test_can_dispatch_webhook_event(): void
    {
        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'is_active' => true,
            'events' => [WebhookService::EVENT_INVOICE_CREATED],
        ]);

        $this->webhookService->dispatch(
            WebhookService::EVENT_INVOICE_CREATED,
            ['invoice_id' => 1, 'amount' => 100]
        );

        $this->assertDatabaseHas('webhook_events', [
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => WebhookService::EVENT_INVOICE_CREATED,
            'status' => 'pending',
        ]);
    }

    public function test_webhook_endpoint_subscription_filter(): void
    {
        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'is_active' => true,
            'events' => [WebhookService::EVENT_INVOICE_CREATED],
        ]);

        $this->assertTrue($endpoint->isSubscribedTo(WebhookService::EVENT_INVOICE_CREATED));
        $this->assertFalse($endpoint->isSubscribedTo(WebhookService::EVENT_PAYMENT_RECEIVED));
    }

    public function test_inactive_endpoint_not_subscribed(): void
    {
        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'is_active' => false,
            'events' => [WebhookService::EVENT_INVOICE_CREATED],
        ]);

        $this->assertFalse($endpoint->isSubscribedTo(WebhookService::EVENT_INVOICE_CREATED));
    }

    public function test_webhook_signature_verification(): void
    {
        $payload = json_encode(['test' => 'data']);
        $secret = 'test-secret';
        $signature = hash_hmac('sha256', $payload, $secret);

        $this->assertTrue(
            WebhookService::verifySignature($payload, $signature, $secret)
        );

        $this->assertFalse(
            WebhookService::verifySignature($payload, 'invalid-signature', $secret)
        );
    }

    public function test_get_available_events(): void
    {
        $events = WebhookService::getAvailableEvents();

        $this->assertIsArray($events);
        $this->assertArrayHasKey(WebhookService::EVENT_INVOICE_CREATED, $events);
        $this->assertArrayHasKey(WebhookService::EVENT_PAYMENT_RECEIVED, $events);
    }

    public function test_dispatch_only_creates_events_for_subscribed_endpoints(): void
    {
        $subscribed = WebhookEndpoint::create([
            'url' => 'https://example.com/subscribed',
            'is_active' => true,
            'events' => [WebhookService::EVENT_INVOICE_CREATED],
        ]);

        $notSubscribed = WebhookEndpoint::create([
            'url' => 'https://example.com/other',
            'is_active' => true,
            'events' => [WebhookService::EVENT_PAYMENT_RECEIVED],
        ]);

        $inactive = WebhookEndpoint::create([
            'url' => 'https://example.com/inactive',
            'is_active' => false,
            'events' => [WebhookService::EVENT_INVOICE_CREATED],
        ]);

        $this->webhookService->dispatch(
            WebhookService::EVENT_INVOICE_CREATED,
            ['invoice_id' => 1]
        );

        $this->assertSame(1, WebhookEvent::count());
        $this->assertDatabaseHas('webhook_events', ['webhook_endpoint_id' => $subscribed->id]);
        $this->assertDatabaseMissing('webhook_events', ['webhook_endpoint_id' => $notSubscribed->id]);
        $this->assertDatabaseMissing('webhook_events', ['webhook_endpoint_id' => $inactive->id]);
    }

    public function test_send_signs_payload_with_verifiable_hmac_sha256(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $secret = 'top-secret';
        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'secret' => $secret,
            'is_active' => true,
        ]);

        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => WebhookService::EVENT_INVOICE_CREATED,
            'payload' => ['invoice_id' => 42],
            'status' => 'pending',
        ]);

        $this->assertTrue($this->webhookService->send($event));

        Http::assertSent(function ($request) use ($secret): bool {
            $header = $request->header('X-Webhook-Signature')[0] ?? '';

            return $header !== ''
                && WebhookService::verifySignature($request->body(), $header, $secret);
        });
    }

    public function test_successful_delivery_marks_event_sent(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'is_active' => true,
        ]);

        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => WebhookService::EVENT_INVOICE_CREATED,
            'payload' => ['invoice_id' => 1],
            'status' => 'pending',
        ]);

        $this->assertTrue($this->webhookService->send($event));

        $event->refresh();
        $this->assertSame('sent', $event->status);
        $this->assertNotNull($event->sent_at);
        $this->assertNotNull($endpoint->fresh()->last_triggered_at);
    }

    public function test_failed_delivery_increments_attempts_with_exponential_backoff(): void
    {
        $this->freezeTime();
        Http::fake(['*' => Http::response('boom', 500)]);

        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'is_active' => true,
            'max_retries' => 3,
            'retry_interval' => 60,
        ]);

        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => WebhookService::EVENT_INVOICE_CREATED,
            'payload' => ['invoice_id' => 1],
            'status' => 'pending',
        ]);

        // First failure: backoff = 60 * 2^0 = 60s
        $this->assertFalse($this->webhookService->send($event));
        $event->refresh();
        $this->assertSame('failed', $event->status);
        $this->assertSame(1, $event->attempts);
        $this->assertEqualsWithDelta(60, now()->diffInSeconds($event->next_retry_at, true), 1.0);

        // Second failure: backoff = 60 * 2^1 = 120s
        $this->assertFalse($this->webhookService->send($event));
        $event->refresh();
        $this->assertSame(2, $event->attempts);
        $this->assertEqualsWithDelta(120, now()->diffInSeconds($event->next_retry_at, true), 1.0);
    }

    public function test_send_blocks_link_local_metadata_endpoint(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $endpoint = WebhookEndpoint::create([
            'url' => 'http://169.254.169.254/latest/meta-data/',
            'is_active' => true,
        ]);

        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => WebhookService::EVENT_INVOICE_CREATED,
            'payload' => ['invoice_id' => 1],
            'status' => 'pending',
        ]);

        $this->assertFalse($this->webhookService->send($event));
        Http::assertNothingSent();
    }

    public function test_send_blocks_localhost_target(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $endpoint = WebhookEndpoint::create([
            'url' => 'https://localhost/webhook',
            'is_active' => true,
        ]);

        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => WebhookService::EVENT_INVOICE_CREATED,
            'payload' => ['invoice_id' => 1],
            'status' => 'pending',
        ]);

        $this->assertFalse($this->webhookService->send($event));
        Http::assertNothingSent();
    }

    public function test_send_allows_external_https_target(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'is_active' => true,
        ]);

        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => WebhookService::EVENT_INVOICE_CREATED,
            'payload' => ['invoice_id' => 1],
            'status' => 'pending',
        ]);

        $this->assertTrue($this->webhookService->send($event));
        Http::assertSentCount(1);
    }

    public function test_assert_safe_url_returns_validated_ip_for_pinning(): void
    {
        // The returned IP is pinned into the connection (CURLOPT_RESOLVE) to close
        // the DNS-rebind TOCTOU. For an IP-literal host it echoes that IP back.
        // (Rebind-between-resolve-and-connect is not deterministically testable.)
        $this->assertSame('203.0.113.10', WebhookService::assertSafeUrl('https://203.0.113.10/webhook'));
    }

    public function test_process_command_flushes_pending_events(): void
    {
        Http::fake(['*' => Http::response('', 200)]);

        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'is_active' => true,
        ]);

        $event = WebhookEvent::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => WebhookService::EVENT_INVOICE_CREATED,
            'payload' => ['invoice_id' => 1],
            'status' => 'pending',
        ]);

        $this->artisan('webhooks:process')->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertSame('sent', $event->fresh()->status);
    }
}
