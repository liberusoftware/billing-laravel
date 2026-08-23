<?php

namespace Tests\Feature\Client;

use App\Filament\Client\Pages\DomainManagement;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsPortalScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owned_domains_query_excludes_subscriptions_the_client_does_not_own(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $ownCustomer = Customer::factory()->create(['email' => 'owner@example.com']);
        $own = Subscription::factory()->create([
            'customer_id' => $ownCustomer->id,
            'domain_name' => 'mine.com',
            'domain_registrar' => 'enom',
        ]);

        $otherCustomer = Customer::factory()->create(['email' => 'other@example.com']);
        $foreign = Subscription::factory()->create([
            'customer_id' => $otherCustomer->id,
            'domain_name' => 'theirs.com',
            'domain_registrar' => 'enom',
        ]);

        $this->actingAs($user);

        $ids = DomainManagement::ownedDomainsQuery()->pluck('id');

        $this->assertTrue($ids->contains($own->id), 'client should see their own domain');
        $this->assertFalse($ids->contains($foreign->id), 'client must not see a domain they do not own');
    }

    public function test_owned_domains_query_ignores_subscriptions_without_a_domain(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);

        $noDomain = Subscription::factory()->create([
            'customer_id' => $customer->id,
            'domain_name' => null,
        ]);

        $this->actingAs($user);

        $this->assertFalse(DomainManagement::ownedDomainsQuery()->pluck('id')->contains($noDomain->id));
    }
}
