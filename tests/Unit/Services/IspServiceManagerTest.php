<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\RadiusClient;
use App\Enums\IspServiceStatus;
use App\Models\IspService;
use App\Services\IspServiceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IspServiceManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_synchronizes_radius_and_activates_subscriber(): void
    {
        $service = IspService::factory()->create();
        $radius = new FakeRadiusClient();

        $activated = app(IspServiceManager::class)->activate($service, $radius);

        $this->assertSame(IspServiceStatus::Active, $activated->status);
        $this->assertNotNull($activated->activated_at);
        $this->assertNotNull($activated->radius_synced_at);
        $this->assertSame([$service->id], $radius->synchronized);
    }

    public function test_accounting_updates_are_idempotent_and_track_bandwidth_usage(): void
    {
        $service = IspService::factory()->create(['status' => IspServiceStatus::Active]);
        $radius = new FakeRadiusClient();
        $manager = app(IspServiceManager::class);

        $payload = [
            'accounting_session_id' => 'session-1',
            'started_at' => now()->subMinute()->toISOString(),
            'input_bytes' => 100,
            'output_bytes' => 900,
            'session_seconds' => 60,
        ];

        $manager->recordAccounting($service, $payload, $radius);
        $manager->recordAccounting($service, $payload, $radius);

        $this->assertSame(1000, $service->refresh()->current_period_usage_bytes);
        $this->assertDatabaseCount('radius_sessions', 1);

        $payload['output_bytes'] = 1400;
        $manager->recordAccounting($service, $payload, $radius);

        $this->assertSame(1500, $service->refresh()->current_period_usage_bytes);
    }

    public function test_data_allowance_enforcement_suspends_and_disconnects_subscriber(): void
    {
        $service = IspService::factory()->create([
            'status' => IspServiceStatus::Active,
            'monthly_data_limit_bytes' => 1000,
        ]);
        $radius = new FakeRadiusClient();

        app(IspServiceManager::class)->recordAccounting($service, [
            'accounting_session_id' => 'over-limit',
            'started_at' => now()->subMinute()->toISOString(),
            'input_bytes' => 400,
            'output_bytes' => 700,
        ], $radius);

        $service->refresh();
        $this->assertSame(IspServiceStatus::Suspended, $service->status);
        $this->assertSame('Monthly data allowance exceeded.', $service->suspension_reason);
        $this->assertSame([$service->id], $radius->suspended);
        $this->assertSame([$service->id], $radius->disconnected);
    }

    public function test_radius_secret_is_encrypted_at_rest_and_hidden_from_serialization(): void
    {
        $service = IspService::factory()->create(['radius_secret' => 'highly-secret-value']);

        $rawSecret = $service->getRawOriginal('radius_secret');

        $this->assertNotSame('highly-secret-value', $rawSecret);
        $this->assertSame('highly-secret-value', $service->radius_secret);
        $this->assertArrayNotHasKey('radius_secret', $service->toArray());
    }
}

final class FakeRadiusClient implements RadiusClient
{
    /** @var list<int> */
    public array $synchronized = [];

    /** @var list<int> */
    public array $suspended = [];

    /** @var list<int> */
    public array $disconnected = [];

    public function synchronizeUser(IspService $service): void
    {
        $this->synchronized[] = $service->id;
    }

    public function suspendUser(IspService $service): void
    {
        $this->suspended[] = $service->id;
    }

    public function disconnectUser(IspService $service): void
    {
        $this->disconnected[] = $service->id;
    }
}
