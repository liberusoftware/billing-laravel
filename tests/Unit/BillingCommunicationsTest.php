<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Communications\Actions\CreateCommunicationService;
use Liberu\Billing\Communications\Actions\TransitionCommunicationNumber;
use Liberu\Billing\Communications\Actions\TransitionCommunicationService;
use Liberu\Billing\Communications\Models\CommunicationNumber;
use Liberu\Billing\Communications\Models\CommunicationService;

uses(RefreshDatabase::class);

it('transitions communication numbers through validated lifecycle states', function () {
    $number = CommunicationNumber::query()->create(['team_id' => 10, 'number' => '+12025550100', 'type' => 'phone', 'status' => 'available']);

    $updated = app(TransitionCommunicationNumber::class)->handle($number, 'suspended');

    expect($updated->status)->toBe('suspended');
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
