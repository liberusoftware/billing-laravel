<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_updates_expiration_and_status(): void
    {
        Http::fake([
            '*' => Http::response('<interface-response><ErrCount>0</ErrCount><ExpirationDate>12/31/2030</ExpirationDate></interface-response>'),
        ]);

        $subscription = Subscription::factory()->create([
            'domain_registrar' => 'enom',
            'domain_name' => 'example.com',
            'domain_expiration_date' => now()->subYear(),
        ]);

        $this->artisan('domains:sync')->assertSuccessful();

        $this->assertTrue($subscription->fresh()->domain_expiration_date->isFuture());
    }
}
