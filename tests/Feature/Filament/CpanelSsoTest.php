<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Client\Resources\HostingAccounts\HostingAccountResource;
use App\Filament\Client\Resources\HostingAccounts\Pages\ListHostingAccounts;
use App\Models\Customer;
use App\Models\HostingAccount;
use App\Models\HostingServer;
use App\Models\User;
use App\Services\ControlPanels\CpanelClient;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Control-panel SSO (seamless cPanel redirect).
 *
 * CpanelClient news-up its own Guzzle client, so Http::fake can't intercept it.
 * As with ProvisioningClientsTest we inject a MockHandler-backed Guzzle client
 * over the protected $client property — exercising real URL building and
 * response parsing while keeping the WHM create_user_session call deterministic.
 */
class CpanelSsoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, Response>  $responses
     * @param  array<int, array>  $history
     */
    private function injectGuzzle(object $client, array $responses, array &$history): void
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));
        (new ReflectionProperty($client, 'client'))->setValue($client, new Client(['handler' => $stack]));
    }

    private function actingInClientPanel(User $user): void
    {
        $this->actingAs($user);
        $panel = Filament::getPanel('client');
        Filament::setCurrentPanel($panel);
        $panel->boot();
    }

    public function test_create_sso_session_builds_create_user_session_call_and_returns_url(): void
    {
        // IP-literal hostname keeps validateHostname() deterministic, and the
        // returned login URL must point at that same host (open-redirect guard).
        $server = HostingServer::factory()->cpanel()->create(['hostname' => '203.0.113.10']);
        $client = new CpanelClient;
        $client->setServer($server);

        $history = [];
        $this->injectGuzzle(
            $client,
            [new Response(200, [], '{"metadata":{"result":1},"data":{"url":"https://203.0.113.10:2083/cpsess123/login/?session=abc"}}')],
            $history
        );

        $url = $client->createSsoSession('bob');

        $this->assertSame('https://203.0.113.10:2083/cpsess123/login/?session=abc', $url);
        $this->assertCount(1, $history);

        $uri = (string) $history[0]['request']->getUri();
        $this->assertStringContainsString('/json-api/create_user_session', $uri);
        $this->assertStringContainsString('user=bob', $uri);
        $this->assertStringContainsString('service=cpaneld', $uri);
    }

    public function test_create_sso_session_returns_null_on_failure(): void
    {
        $server = HostingServer::factory()->cpanel()->create(['hostname' => '203.0.113.10']);
        $client = new CpanelClient;
        $client->setServer($server);

        $history = [];
        $this->injectGuzzle(
            $client,
            [new Response(200, [], '{"metadata":{"result":0,"reason":"no such user"}}')],
            $history
        );

        $this->assertNull($client->createSsoSession('bob'));
    }

    public function test_client_sees_only_their_own_hosting_account(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $mineCustomer = Customer::factory()->create(['email' => 'owner@example.com']);
        $mine = HostingAccount::factory()->cpanel()->create(['customer_id' => $mineCustomer->id]);

        $theirsCustomer = Customer::factory()->create(['email' => 'other@example.com']);
        $theirs = HostingAccount::factory()->cpanel()->create(['customer_id' => $theirsCustomer->id]);

        $this->actingInClientPanel($user);

        Livewire::test(ListHostingAccounts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_open_cpanel_action_redirects_owner_to_sso_url(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $customer = Customer::factory()->create(['email' => 'owner@example.com']);
        $account = HostingAccount::factory()->cpanel()->create(['customer_id' => $customer->id]);

        $mock = Mockery::mock(CpanelClient::class);
        $mock->shouldReceive('setServer')->once();
        $mock->shouldReceive('createSsoSession')->once()->with($account->username)->andReturn('https://sso.example/login');
        $this->app->instance(CpanelClient::class, $mock);

        $this->actingInClientPanel($user);

        Livewire::test(ListHostingAccounts::class)
            ->callAction(TestAction::make('open_cpanel')->table($account))
            ->assertRedirect('https://sso.example/login');
    }

    public function test_client_does_not_own_another_customers_hosting_account(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $mineCustomer = Customer::factory()->create(['email' => 'owner@example.com']);
        $mine = HostingAccount::factory()->cpanel()->create(['customer_id' => $mineCustomer->id]);

        $theirsCustomer = Customer::factory()->create(['email' => 'other@example.com']);
        $theirs = HostingAccount::factory()->cpanel()->create(['customer_id' => $theirsCustomer->id]);

        $this->actingAs($user);

        $this->assertTrue(HostingAccountResource::clientOwns($mine));
        $this->assertFalse(HostingAccountResource::clientOwns($theirs));
    }
}
