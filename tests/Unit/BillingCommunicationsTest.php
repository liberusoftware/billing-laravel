<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Liberu\Billing\Communications\Actions\CreateCallRateRule;
use Liberu\Billing\Communications\Actions\CreateCommunicationService;
use Liberu\Billing\Communications\Actions\CreateVoipAccount;
use Liberu\Billing\Communications\Actions\IngestCallDetailRecord;
use Liberu\Billing\Communications\Actions\ProvisionVoipAccount;
use Liberu\Billing\Communications\Actions\TransitionCommunicationNumber;
use Liberu\Billing\Communications\Actions\TransitionCommunicationService;
use Liberu\Billing\Communications\Contracts\VoiceProvider;
use Liberu\Billing\Communications\Events\CommunicationNumberStatusChanged;
use Liberu\Billing\Communications\Models\CommunicationNumber;
use Liberu\Billing\Communications\Models\CommunicationService;
use Liberu\Billing\Communications\Services\VoiceProviderRegistry;

uses(RefreshDatabase::class);

it('transitions communication numbers through validated lifecycle states', function () {
    Event::fake([CommunicationNumberStatusChanged::class]);
    $number = CommunicationNumber::query()->create(['team_id' => 10, 'number' => '+12025550100', 'type' => 'phone', 'status' => 'available']);

    $updated = app(TransitionCommunicationNumber::class)->handle($number, 'suspended');

    expect($updated->status)->toBe('suspended');
    Event::assertDispatched(CommunicationNumberStatusChanged::class);
});

it('rejects unknown communication number states', function () {
    $number = CommunicationNumber::query()->create(['team_id' => 10, 'number' => '+12025550101', 'type' => 'phone', 'status' => 'available']);

    expect(fn () => app(TransitionCommunicationNumber::class)->handle($number, 'unknown'))
        ->toThrow(InvalidArgumentException::class);
});

it('does not reactivate a communication service after its persisted state becomes cancelled', function (): void {
    $service = app(CreateCommunicationService::class)->handle(10, ['name' => 'Transactional email']);
    $service->refresh();
    CommunicationService::query()->whereKey($service->getKey())->update(['status' => 'cancelled']);

    expect(fn () => app(TransitionCommunicationService::class)->handle($service, 'active'))
        ->toThrow(InvalidArgumentException::class, 'Cancelled communication services cannot be reactivated.');
});

it('does not reactivate a communication number after its persisted state becomes released', function (): void {
    $number = CommunicationNumber::query()->create(['team_id' => 10, 'number' => '+12025550102', 'type' => 'phone', 'status' => 'available']);
    $number->refresh();
    CommunicationNumber::query()->whereKey($number->getKey())->update(['status' => 'released']);

    expect(fn () => app(TransitionCommunicationNumber::class)->handle($number, 'active'))
        ->toThrow(InvalidArgumentException::class, 'Released communication numbers cannot be reactivated.');
});

it('provisions voice accounts through the registered provider', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    $customer = Customer::factory()->create(['team_id' => $team->id]);
    app(VoiceProviderRegistry::class)->register(new class() implements VoiceProvider
    {
        public function key(): string
        {
            return ' asterisk ';
        }

        public function provision(array $attributes): array
        {
            return ['external_id' => 'voice-1'];
        }
    });
    $account = app(CreateVoipAccount::class)->handle($team->id, ['customer_id' => $customer->id, 'platform' => 'asterisk', 'sip_username' => 'sip-100', 'sip_secret' => 'secret']);

    $provisioned = app(ProvisionVoipAccount::class)->handle($account);

    expect($provisioned->status)->toBe('active')->and($provisioned->provider_result)->toBe(['external_id' => 'voice-1']);
});

it('rates voice calls by longest prefix and remains idempotent', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $team = $user->currentTeam;
    $customer = Customer::factory()->create(['team_id' => $team->id]);
    $account = app(CreateVoipAccount::class)->handle($team->id, ['customer_id' => $customer->id, 'platform' => 'asterisk', 'sip_username' => 'sip-101', 'sip_secret' => 'secret']);
    app(CreateCallRateRule::class)->handle($team->id, ['name' => 'Generic', 'destination_prefix' => '+44', 'rate_per_minute' => 1, 'billing_increment_seconds' => 60]);
    app(CreateCallRateRule::class)->handle($team->id, ['name' => 'London', 'destination_prefix' => '+4420', 'connection_fee' => 0.10, 'rate_per_minute' => 0.20, 'billing_increment_seconds' => 30, 'currency' => 'GBP']);
    $data = ['external_id' => 'call-1', 'source' => '+15551234567', 'destination' => '+442071234567', 'started_at' => now()->subMinute()->toISOString(), 'duration_seconds' => 61];

    $cdr = app(IngestCallDetailRecord::class)->handle($account, $data);
    $duplicate = app(IngestCallDetailRecord::class)->handle($account, $data);

    expect($cdr->billable_seconds)->toBe(90)->and((float) $cdr->rated_cost)->toBe(0.4)->and($duplicate->getKey())->toBe($cdr->getKey())
        ->and($account->refresh()->current_usage_cost)->toBe('0.4000');
});
