<?php

use Liberu\Billing\Hosting\Actions\TransitionHostingAccount;
use Liberu\Billing\Hosting\Actions\TransitionHostingCapability;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Liberu\Billing\Hosting\Models\HostingCapability;

it('does not reactivate cancelled hosting accounts', function (): void {
    $account = HostingAccount::query()->create([
        'team_id' => 1,
        'name' => 'example-hosting',
        'status' => 'cancelled',
    ]);

    expect(fn () => app(TransitionHostingAccount::class)->handle($account, 'active'))
        ->toThrow(InvalidArgumentException::class, 'Cancelled hosting accounts cannot be reactivated.');
});

it('does not reactivate hosting capabilities after their persisted state becomes cancelled', function (): void {
    $capability = HostingCapability::query()->create([
        'team_id' => 1,
        'name' => 'SSL certificate',
        'type' => 'ssl',
        'status' => 'pending',
        'configuration' => [],
    ]);
    $capability->refresh();
    HostingCapability::query()->whereKey($capability->getKey())->update(['status' => 'cancelled']);

    expect(fn () => app(TransitionHostingCapability::class)->handle($capability, 'active'))
        ->toThrow(InvalidArgumentException::class, 'Cancelled hosting capabilities cannot be reactivated.');
});
