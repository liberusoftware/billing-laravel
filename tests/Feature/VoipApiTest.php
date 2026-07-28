<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Team;
use App\Models\User;
use App\Models\VoipAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoipApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_manage_sip_account_rates_dids_and_ingest_cdr(): void
    {
        [, $team] = $this->operator();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $accountResponse = $this->postJson('/api/voip/accounts', [
            'customer_id' => $customer->id,
            'platform' => 'asterisk',
            'sip_username' => 'sip-100',
            'sip_secret' => 'secure-sip-password',
            'caller_id' => '+15551234567',
        ])->assertCreated()->assertJsonMissingPath('sip_secret');
        $accountId = $accountResponse->json('id');

        $this->postJson('/api/voip/rates', [
            'name' => 'US',
            'destination_prefix' => '+1',
            'rate_per_minute' => 0.05,
            'billing_increment_seconds' => 60,
        ])->assertCreated();

        $didResponse = $this->postJson('/api/voip/dids', [
            'number' => '+15559876543',
            'country_code' => 'US',
            'monthly_price' => 2.50,
        ])->assertCreated();
        $this->postJson('/api/voip/dids/'.$didResponse->json('id').'/assign', [
            'voip_account_id' => $accountId,
        ])->assertOk()->assertJsonPath('status', 'assigned');

        $this->postJson("/api/voip/accounts/{$accountId}/cdrs", [
            'external_id' => 'pbx-call-1',
            'source' => '+15551234567',
            'destination' => '+15550001111',
            'started_at' => now()->subMinute()->toISOString(),
            'duration_seconds' => 45,
            'disposition' => 'answered',
        ])->assertAccepted()
            ->assertJsonPath('billable_seconds', 60)
            ->assertJsonPath('rated_cost', '0.0500');

        $this->getJson("/api/voip/accounts/{$accountId}")
            ->assertOk()
            ->assertJsonMissingPath('sip_secret')
            ->assertJsonCount(1, 'did_numbers')
            ->assertJsonCount(1, 'call_detail_records');
    }

    public function test_voip_api_rejects_cross_tenant_access_and_relationships(): void
    {
        [, $team] = $this->operator();
        $otherTeam = Team::factory()->create();
        $otherCustomer = Customer::factory()->create(['team_id' => $otherTeam->id]);
        $otherAccount = VoipAccount::factory()->create([
            'team_id' => $otherTeam->id,
            'customer_id' => $otherCustomer->id,
        ]);

        $this->getJson("/api/voip/accounts/{$otherAccount->id}")->assertNotFound();
        $this->postJson('/api/voip/accounts', [
            'customer_id' => $otherCustomer->id,
            'platform' => '3cx',
            'sip_username' => 'cross-tenant',
            'sip_secret' => 'secure-sip-password',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('voip_accounts', [
            'team_id' => $team->id,
            'sip_username' => 'cross-tenant',
        ]);
    }

    /** @return array{User, Team} */
    private function operator(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->update(['current_team_id' => $team->id]);
        Sanctum::actingAs($user, ['voip:read', 'voip:write']);

        return [$user, $team];
    }
}
