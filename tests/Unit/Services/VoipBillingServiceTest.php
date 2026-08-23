<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\VoipPlatformClient;
use App\Enums\VoipAccountStatus;
use App\Models\CallRateRule;
use App\Models\Subscription;
use App\Models\VoipAccount;
use App\Services\VoipBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoipBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_activates_and_synchronizes_sip_account(): void
    {
        $account = VoipAccount::factory()->create();
        $client = new FakeVoipPlatformClient;

        $provisioned = app(VoipBillingService::class)->provision($account, $client);

        $this->assertSame(VoipAccountStatus::Active, $provisioned->status);
        $this->assertNotNull($provisioned->provisioned_at);
        $this->assertSame([$account->id], $client->provisioned);
    }

    public function test_cdr_uses_longest_prefix_and_rounds_to_billing_increment(): void
    {
        $account = VoipAccount::factory()->create();
        CallRateRule::query()->create([
            'team_id' => $account->team_id,
            'name' => 'Generic UK',
            'destination_prefix' => '+44',
            'rate_per_minute' => 1,
            'billing_increment_seconds' => 60,
        ]);
        $specific = CallRateRule::query()->create([
            'team_id' => $account->team_id,
            'name' => 'London',
            'destination_prefix' => '+4420',
            'connection_fee' => 0.10,
            'rate_per_minute' => 0.20,
            'billing_increment_seconds' => 30,
            'currency' => 'GBP',
        ]);

        $cdr = app(VoipBillingService::class)->ingestCdr($account, $this->cdr([
            'destination' => '+442071234567',
            'duration_seconds' => 61,
        ]));

        $this->assertSame($specific->id, $cdr->call_rate_rule_id);
        $this->assertSame(90, $cdr->billable_seconds);
        $this->assertEquals(0.40, (float) $cdr->rated_cost);
        $this->assertSame('GBP', $cdr->currency);
    }

    public function test_duplicate_cdr_is_idempotent_and_usage_is_recorded_once(): void
    {
        $account = VoipAccount::factory()->create();
        $subscription = Subscription::factory()->create([
            'team_id' => $account->team_id,
            'customer_id' => $account->customer_id,
        ]);
        $account->update(['subscription_id' => $subscription->id]);
        CallRateRule::query()->create([
            'team_id' => $account->team_id,
            'name' => 'All',
            'destination_prefix' => '+',
            'rate_per_minute' => 0.50,
            'billing_increment_seconds' => 60,
        ]);
        $service = app(VoipBillingService::class);
        $cdr = $this->cdr(['duration_seconds' => 120]);

        $service->ingestCdr($account, $cdr);
        $service->ingestCdr($account, $cdr);

        $this->assertDatabaseCount('call_detail_records', 1);
        $this->assertDatabaseCount('usage_records', 1);
        $this->assertEquals(1.0, (float) $account->refresh()->current_usage_cost);
        $this->assertDatabaseHas('usage_records', [
            'subscription_id' => $subscription->id,
            'metric_name' => 'voip_minutes',
            'quantity' => 2,
        ]);
    }

    public function test_high_cost_duration_destination_and_credit_limit_create_fraud_alerts(): void
    {
        config([
            'voip.fraud.single_call_cost' => 5,
            'voip.fraud.call_duration_seconds' => 60,
            'voip.fraud.high_risk_prefixes' => ['+999'],
        ]);
        $account = VoipAccount::factory()->create(['credit_limit' => 5]);
        CallRateRule::query()->create([
            'team_id' => $account->team_id,
            'name' => 'Risk',
            'destination_prefix' => '+999',
            'rate_per_minute' => 10,
            'billing_increment_seconds' => 60,
        ]);

        app(VoipBillingService::class)->ingestCdr($account, $this->cdr([
            'destination' => '+999123',
            'duration_seconds' => 120,
        ]));

        $this->assertDatabaseHas('voip_fraud_alerts', ['rule' => 'high_call_cost', 'severity' => 'high']);
        $this->assertDatabaseHas('voip_fraud_alerts', ['rule' => 'long_duration']);
        $this->assertDatabaseHas('voip_fraud_alerts', ['rule' => 'high_risk_destination']);
        $this->assertDatabaseHas('voip_fraud_alerts', ['rule' => 'credit_limit', 'severity' => 'critical']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function cdr(array $overrides = []): array
    {
        return [
            'external_id' => 'call-1',
            'source' => '+15551234567',
            'destination' => '+441234567890',
            'started_at' => now()->subMinutes(2)->toISOString(),
            'answered_at' => now()->subMinutes(2)->toISOString(),
            'ended_at' => now()->toISOString(),
            'duration_seconds' => 60,
            'disposition' => 'answered',
            ...$overrides,
        ];
    }
}

final class FakeVoipPlatformClient implements VoipPlatformClient
{
    /** @var list<int> */
    public array $provisioned = [];

    public function provisionAccount(VoipAccount $account): void
    {
        $this->provisioned[] = $account->id;
    }

    public function synchronizeAccount(VoipAccount $account): void {}

    public function suspendAccount(VoipAccount $account): void {}
}
