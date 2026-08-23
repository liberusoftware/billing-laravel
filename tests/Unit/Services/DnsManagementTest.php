<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Subscription;
use App\Services\DomainService;
use App\Services\Registrars\EnomClient;
use App\Services\Registrars\ResellerClubClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DNS + WHOIS self-management (N3). The registrar clients new-up Guzzle in their
 * constructors, so the wire call cannot be intercepted with Http::fake(); they are
 * constructor-injected into DomainService, so we mock the injected client instead
 * (same pattern as DomainServiceTest).
 */
class DnsManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscription(array $attributes = []): Subscription
    {
        $customer = Customer::factory()->create();

        return Subscription::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'domain_name' => 'example.com',
            'domain_registrar' => 'enom',
        ], $attributes));
    }

    public function test_get_dns_records_delegates_to_registrar_client(): void
    {
        $subscription = $this->makeSubscription();
        $records = [['id' => '1', 'type' => 'A', 'name' => '@', 'content' => '1.2.3.4', 'ttl' => 3600]];

        $this->mock(EnomClient::class, function ($mock) use ($records): void {
            $mock->shouldReceive('getDnsRecords')->once()->with('example.com')->andReturn($records);
        });

        $this->assertSame($records, app(DomainService::class)->getDnsRecords($subscription));
    }

    public function test_add_dns_record_delegates_and_returns_bool(): void
    {
        $subscription = $this->makeSubscription();
        $record = ['type' => 'A', 'name' => 'www', 'content' => '1.2.3.4', 'ttl' => 3600];

        $this->mock(EnomClient::class, function ($mock) use ($record): void {
            $mock->shouldReceive('addDnsRecord')->once()->with('example.com', $record)->andReturn(true);
        });

        $this->assertTrue(app(DomainService::class)->addDnsRecord($subscription, $record));
    }

    public function test_delete_dns_record_delegates_and_returns_bool(): void
    {
        $subscription = $this->makeSubscription();

        $this->mock(EnomClient::class, function ($mock): void {
            $mock->shouldReceive('deleteDnsRecord')->once()->with('example.com', 'rec-9')->andReturn(true);
        });

        $this->assertTrue(app(DomainService::class)->deleteDnsRecord($subscription, 'rec-9'));
    }

    public function test_dns_methods_use_resellerclub_when_subscription_registrar_is_resellerclub(): void
    {
        $subscription = $this->makeSubscription(['domain_registrar' => 'resellerclub']);

        $this->mock(ResellerClubClient::class, function ($mock): void {
            $mock->shouldReceive('getDnsRecords')->once()->with('example.com')->andReturn([]);
        });

        $this->assertSame([], app(DomainService::class)->getDnsRecords($subscription));
    }

    public function test_get_whois_contacts_delegates_to_registrar_client(): void
    {
        $subscription = $this->makeSubscription();
        $contacts = ['registrant' => ['name' => 'Jane', 'email' => 'jane@example.com']];

        $this->mock(EnomClient::class, function ($mock) use ($contacts): void {
            $mock->shouldReceive('getWhoisContacts')->once()->with('example.com')->andReturn($contacts);
        });

        $this->assertSame($contacts, app(DomainService::class)->getWhoisContacts($subscription));
    }

    public function test_update_whois_contacts_delegates_and_returns_bool(): void
    {
        $subscription = $this->makeSubscription();
        $contacts = ['registrant' => ['name' => 'Jane']];

        $this->mock(EnomClient::class, function ($mock) use ($contacts): void {
            $mock->shouldReceive('updateWhoisContacts')->once()->with('example.com', $contacts)->andReturn(true);
        });

        $this->assertTrue(app(DomainService::class)->updateWhoisContacts($subscription, $contacts));
    }
}
