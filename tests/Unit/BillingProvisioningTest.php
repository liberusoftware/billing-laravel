<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Provisioning\Actions\CreateProvisionedService;
use Liberu\Billing\Provisioning\Actions\QueueProvisioningOperation;
use Liberu\Billing\Provisioning\Actions\ReconcileProvisionedService;
use Liberu\Billing\Provisioning\Actions\RunProvisioningOperation;
use Liberu\Billing\Provisioning\Actions\TransitionProvisionedService;
use Liberu\Billing\Provisioning\Contracts\ProvisioningDriver;
use Liberu\Billing\Provisioning\Enums\ProvisioningState;
use Liberu\Billing\Provisioning\Models\ProvisionedService;
use Liberu\Billing\Provisioning\Services\ProvisioningDriverRegistry;

uses(RefreshDatabase::class);

it('creates a pending provisioned service through the domain action', function () {
    $service = app(CreateProvisionedService::class)->execute(['team_id' => 10, 'provider' => 'test']);

    expect($service->state)->toBe(ProvisioningState::Pending)
        ->and($service->team_id)->toBe(10)
        ->and($service->provider)->toBe('test');
});

it('runs a queued provider operation and records the external identity', function () {
    $registry = app(ProvisioningDriverRegistry::class);
    $registry->register('test', new class() implements ProvisioningDriver
    {
        public function provision(ProvisionedService $service): string
        {
            return 'external-123';
        }

        public function deprovision(ProvisionedService $service): void {}

        public function poll(ProvisionedService $service): array
        {
            return ['state' => ProvisioningState::Active->value, 'external_id' => 'external-123'];
        }
    });

    $service = ProvisionedService::query()->create(['team_id' => 10, 'provider' => 'test', 'state' => ProvisioningState::Pending, 'metadata' => []]);
    $operation = app(QueueProvisioningOperation::class)->execute($service, 'provision');
    $completed = app(RunProvisioningOperation::class)->execute($operation);

    expect($completed->status)->toBe('completed')
        ->and($service->refresh()->state)->toBe(ProvisioningState::Active)
        ->and($service->external_id)->toBe('external-123');
});

it('records provider failures and schedules a retry', function () {
    $registry = app(ProvisioningDriverRegistry::class);
    $registry->register('failing', new class() implements ProvisioningDriver
    {
        public function provision(ProvisionedService $service): string
        {
            throw new RuntimeException('Provider unavailable');
        }

        public function deprovision(ProvisionedService $service): void {}

        public function poll(ProvisionedService $service): array
        {
            return ['state' => ProvisioningState::Failed->value, 'error' => 'Provider unavailable'];
        }
    });

    $service = ProvisionedService::query()->create(['team_id' => 10, 'provider' => 'failing', 'state' => ProvisioningState::Pending, 'metadata' => []]);
    $operation = app(QueueProvisioningOperation::class)->execute($service, 'provision');

    expect(fn () => app(RunProvisioningOperation::class)->execute($operation))
        ->toThrow(RuntimeException::class, 'Provider unavailable');

    expect($operation->refresh()->status)->toBe('failed')
        ->and($operation->next_poll_at)->not->toBeNull()
        ->and($operation->error)->toBe('Provider unavailable');
});

it('does not execute a stale operation after it has already completed', function (): void {
    $registry = app(ProvisioningDriverRegistry::class);
    $registry->register('already-complete', new class() implements ProvisioningDriver
    {
        public function provision(ProvisionedService $service): string
        {
            throw new RuntimeException('The completed operation must not call the provider.');
        }

        public function deprovision(ProvisionedService $service): void {}

        public function poll(ProvisionedService $service): array
        {
            return [];
        }
    });

    $service = ProvisionedService::query()->create(['team_id' => 10, 'provider' => 'already-complete', 'state' => ProvisioningState::Pending, 'metadata' => []]);
    $operation = app(QueueProvisioningOperation::class)->execute($service, 'provision');
    $operation->refresh();
    $operation->update(['status' => 'completed']);

    expect(app(RunProvisioningOperation::class)->execute($operation)->status)->toBe('completed');
});

it('does not transition a service using a stale persisted state', function (): void {
    $service = app(CreateProvisionedService::class)->execute(['team_id' => 10, 'provider' => 'test']);
    $service->refresh();
    ProvisionedService::query()->whereKey($service->getKey())->update(['state' => ProvisioningState::Active->value]);

    expect(fn () => app(TransitionProvisionedService::class)->execute($service, ProvisioningState::Provisioning))
        ->toThrow(InvalidArgumentException::class, 'Invalid provisioning transition from [active] to [provisioning].');
});

it('reconciles the persisted provisioned service state', function (): void {
    $service = app(CreateProvisionedService::class)->execute(['team_id' => 10, 'provider' => 'test']);
    $service->refresh();
    ProvisionedService::query()->whereKey($service->getKey())->update(['external_id' => 'provider-123']);

    $reconciled = app(ReconcileProvisionedService::class)->execute($service);

    expect($reconciled->external_id)->toBe('provider-123')
        ->and($reconciled->last_reconciled_at)->not->toBeNull();
});
