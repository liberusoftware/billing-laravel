<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Isp\Actions\CreateAccessService;
use Liberu\Billing\Isp\Actions\CreateIspCapability;
use Liberu\Billing\Isp\Actions\RecordRadiusAccounting;
use Liberu\Billing\Isp\Actions\ResetUsagePeriod;
use Liberu\Billing\Isp\Actions\SynchronizeAccessService;
use Liberu\Billing\Isp\Actions\TransitionAccessService;
use Liberu\Billing\Isp\Actions\TransitionIspCapability;
use Liberu\Billing\Isp\Contracts\NetworkAdapter;
use Liberu\Billing\Isp\Services\NetworkAdapterRegistry;

uses(RefreshDatabase::class);

it('does not reactivate cancelled ISP access services or capabilities', function (): void {
    $service = app(CreateAccessService::class)->handle(10, ['name' => 'Broadband']);
    $service = app(TransitionAccessService::class)->handle($service, 'cancelled');
    $capability = app(CreateIspCapability::class)->handle(10, ['type' => 'radius', 'name' => 'RADIUS']);
    $capability = app(TransitionIspCapability::class)->handle($capability, 'cancelled');

    expect(fn () => app(TransitionAccessService::class)->handle($service, 'active'))
        ->toThrow(LogicException::class)
        ->and(fn () => app(TransitionIspCapability::class)->handle($capability, 'active'))
        ->toThrow(LogicException::class);
});

it('records idempotent radius accounting and suspends at the usage limit', function (): void {
    $service = app(CreateAccessService::class)->handle(10, ['name' => 'Broadband', 'monthly_data_limit_bytes' => 10]);
    $accounting = ['accounting_session_id' => 'session-1', 'started_at' => '2026-08-29 10:00:00', 'input_bytes' => 4, 'output_bytes' => 3];

    app(RecordRadiusAccounting::class)->execute($service, $accounting);
    $updated = app(RecordRadiusAccounting::class)->execute($service, [...$accounting, 'input_bytes' => 6, 'output_bytes' => 4]);
    $updated = app(RecordRadiusAccounting::class)->execute($service, [...$accounting, 'input_bytes' => 6, 'output_bytes' => 4]);

    expect($updated->total_bytes)->toBe(10)
        ->and($service->refresh()->current_period_usage_bytes)->toBe(10)
        ->and($service->refresh()->status)->toBe('suspended')
        ->and(app(ResetUsagePeriod::class)->execute($service)->current_period_usage_bytes)->toBe(0);
});

it('synchronizes an access service through the registered network adapter', function (): void {
    app(NetworkAdapterRegistry::class)->register(new class() implements NetworkAdapter
    {
        public function key(): string
        {
            return 'test-radius';
        }

        public function install(array $attributes): array
        {
            return ['adapter' => 'test-radius', 'reference' => 'ext-1'];
        }

        public function suspend(array $attributes): array
        {
            return [];
        }

        public function remove(array $attributes): array
        {
            return [];
        }
    });
    $service = app(CreateAccessService::class)->handle(10, ['name' => 'Fiber']);

    $synced = app(SynchronizeAccessService::class)->execute($service, 'test-radius');

    expect($synced->radius_synced_at)->not->toBeNull()->and($synced->metadata['external_reference'])->toBe('ext-1');
});
