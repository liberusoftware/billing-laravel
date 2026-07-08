<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Subscription;
use App\Services\DomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_initiates_with_auth_code(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount></interface-response>'),
        ]);
        $subscription = Subscription::factory()->create();

        app(DomainService::class)->transferDomain($subscription, 'example.com', 'AUTH123', 'enom');

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'command=TP_CreateOrder')
                && str_contains($request->url(), 'AuthInfo=AUTH123');
        });
    }

    public function test_transfer_status_persists(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount></interface-response>'),
        ]);
        $subscription = Subscription::factory()->create();

        app(DomainService::class)->transferDomain($subscription, 'example.com', 'AUTH123', 'enom');

        $this->assertSame('pending', $subscription->fresh()->domain_transfer_status);
    }
}
