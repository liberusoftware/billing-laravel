<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Isp\Actions\CreateAccessService;
use Liberu\Billing\Isp\Actions\CreateIspCapability;
use Liberu\Billing\Isp\Actions\TransitionAccessService;
use Liberu\Billing\Isp\Actions\TransitionIspCapability;

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
