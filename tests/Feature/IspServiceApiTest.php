<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BroadbandTechnology;
use App\Enums\RadiusPlatform;
use App\Models\Customer;
use App\Models\IspService;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IspServiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_create_and_read_tenant_isp_service_without_exposing_secret(): void
    {
        [$user, $team] = $this->operator();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $response = $this->postJson('/api/isp-services', [
            'customer_id' => $customer->id,
            'technology' => BroadbandTechnology::Ftth->value,
            'radius_platform' => RadiusPlatform::FreeRadius->value,
            'radius_username' => 'subscriber-100',
            'radius_secret' => 'a-secure-radius-secret',
            'download_limit_bps' => 100_000_000,
            'upload_limit_bps' => 20_000_000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('team_id', $team->id)
            ->assertJsonMissingPath('radius_secret');

        $serviceId = $response->json('id');
        $this->getJson("/api/isp-services/{$serviceId}")
            ->assertOk()
            ->assertJsonPath('customer.id', $customer->id)
            ->assertJsonMissingPath('radius_secret');
    }

    public function test_tenant_cannot_read_or_assign_another_tenants_customer(): void
    {
        [, $team] = $this->operator();
        $otherTeam = Team::factory()->create();
        $otherCustomer = Customer::factory()->create(['team_id' => $otherTeam->id]);
        $otherService = IspService::factory()->create([
            'team_id' => $otherTeam->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $this->getJson("/api/isp-services/{$otherService->id}")->assertNotFound();

        $this->postJson('/api/isp-services', [
            'customer_id' => $otherCustomer->id,
            'technology' => BroadbandTechnology::Wireless->value,
            'radius_platform' => RadiusPlatform::MikroTik->value,
            'radius_username' => 'cross-tenant',
            'radius_secret' => 'a-secure-radius-secret',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('isp_services', [
            'team_id' => $team->id,
            'radius_username' => 'cross-tenant',
        ]);
    }

    public function test_operator_can_activate_and_account_for_subscriber_through_api(): void
    {
        [, $team] = $this->operator();
        config([
            'radius.platforms.freeradius.url' => 'https://radius.example.test/api',
            'radius.platforms.freeradius.token' => 'trusted-token',
        ]);
        Http::fake(['*' => Http::response([], 204)]);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $service = IspService::factory()->create([
            'team_id' => $team->id,
            'customer_id' => $customer->id,
            'radius_platform' => RadiusPlatform::FreeRadius,
        ]);

        $this->postJson("/api/isp-services/{$service->id}/activate")
            ->assertOk()
            ->assertJsonPath('status', 'active');

        $this->postJson("/api/isp-services/{$service->id}/accounting", [
            'accounting_session_id' => 'api-session',
            'started_at' => now()->subMinute()->toISOString(),
            'input_bytes' => 100,
            'output_bytes' => 200,
            'session_seconds' => 60,
        ])->assertAccepted()
            ->assertJsonPath('accounting_session_id', 'api-session');

        $this->assertSame(300, $service->refresh()->current_period_usage_bytes);
    }

    /**
     * @return array{User, Team}
     */
    private function operator(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->update(['current_team_id' => $team->id]);
        Sanctum::actingAs($user, ['isp-services:read', 'isp-services:write']);

        return [$user, $team];
    }
}
