<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Subscription;
use App\Services\DomainPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainValueAddsTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_protection_flag_persists(): void
    {
        $subscription = Subscription::factory()->create(['id_protection' => true]);

        $this->assertTrue($subscription->fresh()->id_protection);
    }

    public function test_free_domain_applied_with_hosting(): void
    {
        $service = app(DomainPricingService::class);

        $this->assertSame(0.0, $service->priceForDomain(12.99, true));
        $this->assertSame(12.99, $service->priceForDomain(12.99, false));
    }
}
