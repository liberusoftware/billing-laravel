<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\HostingAccount;
use App\Models\Subscription;
use App\Models\Tld;
use App\Services\DomainPricingService;
use App\Services\DomainService;
use App\Services\Registrars\EnomClient;
use App\Services\Registrars\ResellerClubClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The registrar clients new-up Guzzle in their constructors, so Http::fake()
     * cannot intercept them. They are constructor-injected into DomainService,
     * so we mock the injected client instead (same pattern as BillingServiceTest).
     */
    private function makeSubscription(array $attributes = []): Subscription
    {
        $customer = Customer::factory()->create();

        return Subscription::factory()->create(array_merge([
            'customer_id' => $customer->id,
        ], $attributes));
    }

    public function test_register_domain_sets_subscription_state_and_updates_existing_hosting_account(): void
    {
        $expiration = Carbon::parse('2027-01-01');
        $subscription = $this->makeSubscription();

        // An existing hosting account (created during provisioning) gets its domain set.
        $hostingAccount = HostingAccount::create([
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'username' => 'acct1',
            'domain' => 'old.example.com',
            'package' => 'basic',
            'status' => 'active',
        ]);

        $this->mock(EnomClient::class, function ($mock) use ($subscription, $expiration): void {
            $mock->shouldReceive('registerDomain')
                ->once()
                ->with('example.com', $subscription->customer_id)
                ->andReturn(['expiration_date' => $expiration]);
        });

        $result = app(DomainService::class)->registerDomain($subscription, 'example.com', 'enom');

        $this->assertSame($expiration, $result['expiration_date']);

        $subscription->refresh();
        $this->assertEquals('example.com', $subscription->domain_name);
        $this->assertEquals('enom', $subscription->domain_registrar);
        $this->assertEquals($expiration->toDateString(), $subscription->domain_expiration_date->toDateString());

        $this->assertEquals('example.com', $hostingAccount->fresh()->domain);
    }

    public function test_register_domain_does_not_create_an_incomplete_hosting_account(): void
    {
        $subscription = $this->makeSubscription();

        $this->mock(EnomClient::class, function ($mock): void {
            $mock->shouldReceive('registerDomain')
                ->once()
                ->andReturn(['expiration_date' => Carbon::parse('2027-01-01')]);
        });

        app(DomainService::class)->registerDomain($subscription, 'example.com', 'enom');

        $this->assertEquals('example.com', $subscription->fresh()->domain_name);
        // No hosting account is fabricated when none exists (it lacks required fields).
        $this->assertDatabaseMissing('hosting_accounts', ['subscription_id' => $subscription->id]);
    }

    public function test_register_domain_uses_resellerclub_client_when_selected(): void
    {
        $subscription = $this->makeSubscription();

        $this->mock(ResellerClubClient::class, function ($mock): void {
            $mock->shouldReceive('registerDomain')
                ->once()
                ->andReturn(['expiration_date' => Carbon::parse('2028-05-05')]);
        });

        app(DomainService::class)->registerDomain($subscription, 'foo.org', 'resellerclub');

        $subscription->refresh();
        $this->assertEquals('foo.org', $subscription->domain_name);
        $this->assertEquals('resellerclub', $subscription->domain_registrar);
    }

    public function test_register_domain_rejects_unsupported_registrar(): void
    {
        $subscription = $this->makeSubscription();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unsupported domain registrar: godaddy');

        app(DomainService::class)->registerDomain($subscription, 'example.com', 'godaddy');
    }

    public function test_transfer_domain_sets_subscription_state_and_updates_hosting_account(): void
    {
        $expiration = Carbon::parse('2027-06-06');
        $subscription = $this->makeSubscription(['domain_registrar' => 'enom']);

        $hostingAccount = HostingAccount::create([
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'username' => 'acct1',
            'domain' => 'old.example.com',
            'package' => 'basic',
            'status' => 'active',
        ]);

        $this->mock(EnomClient::class, function ($mock) use ($subscription, $expiration): void {
            $mock->shouldReceive('transferDomain')
                ->once()
                ->with('moved.com', 'AUTH-123', $subscription->customer_id)
                ->andReturn(['expiration_date' => $expiration]);
        });

        $result = app(DomainService::class)->transferDomain($subscription, 'moved.com', 'AUTH-123', 'enom');

        $this->assertSame($expiration, $result['expiration_date']);

        $subscription->refresh();
        $this->assertEquals('moved.com', $subscription->domain_name);
        $this->assertEquals('enom', $subscription->domain_registrar);
        $this->assertEquals($expiration->toDateString(), $subscription->domain_expiration_date->toDateString());
        $this->assertEquals('moved.com', $hostingAccount->fresh()->domain);
    }

    public function test_renew_domain_extends_expiration_date(): void
    {
        $newExpiration = Carbon::parse('2029-01-01');
        $subscription = $this->makeSubscription([
            'domain_registrar' => 'enom',
            'domain_name' => 'example.com',
            'domain_expiration_date' => Carbon::parse('2028-01-01'),
        ]);

        $this->mock(EnomClient::class, function ($mock) use ($newExpiration): void {
            $mock->shouldReceive('renewDomain')
                ->once()
                ->with('example.com', 2)
                ->andReturn(['new_expiration_date' => $newExpiration]);
        });

        app(DomainService::class)->renewDomain($subscription, 2);

        $this->assertEquals(
            $newExpiration->toDateString(),
            $subscription->fresh()->domain_expiration_date->toDateString()
        );
    }

    public function test_calculate_domain_price_applies_percentage_markup(): void
    {
        Tld::create([
            'name' => '.com',
            'enom_cost' => 10.00,
            'base_price' => 10.00,
            'markup_type' => 'percentage',
            'markup_value' => 25,
        ]);

        $price = app(DomainPricingService::class)->calculateDomainPrice('example.com');

        // 10 * (1 + 25/100) = 12.50
        $this->assertEquals(12.50, (float) $price);
    }

    public function test_calculate_domain_price_applies_fixed_markup(): void
    {
        Tld::create([
            'name' => '.net',
            'enom_cost' => 8.00,
            'base_price' => 8.00,
            'markup_type' => 'fixed',
            'markup_value' => 3.50,
        ]);

        $price = app(DomainPricingService::class)->calculateDomainPrice('example.net');

        // 8 + 3.50 = 11.50
        $this->assertEquals(11.50, (float) $price);
    }

    public function test_calculate_domain_price_falls_back_to_base_price(): void
    {
        Tld::create([
            'name' => '.io',
            'enom_cost' => 30.00,
            'base_price' => 45.00,
            'markup_type' => 'none',
            'markup_value' => 0,
        ]);

        $price = app(DomainPricingService::class)->calculateDomainPrice('example.io');

        $this->assertEquals(45.00, (float) $price);
    }

    public function test_calculate_domain_price_rejects_unsupported_tld(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('TLD not supported: .xyz');

        app(DomainPricingService::class)->calculateDomainPrice('example.xyz');
    }
}
