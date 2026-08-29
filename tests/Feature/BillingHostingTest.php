<?php

use Liberu\Billing\Hosting\Actions\TransitionHostingAccount;
use Liberu\Billing\Hosting\Actions\TransitionHostingCapability;
use Liberu\Billing\Hosting\Contracts\HostingDriver;
use Liberu\Billing\Hosting\Models\HostingAccount;
use Liberu\Billing\Hosting\Models\HostingCapability;
use Liberu\Billing\Hosting\Services\HostingDriverRegistry;

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

it('normalizes hosting driver keys for registration and lookup', function (): void {
    $registry = new HostingDriverRegistry();
    $driver = new class() implements HostingDriver
    {
        public function key(): string
        {
            return ' cPanel ';
        }

        public function provision(array $attributes): array
        {
            return [];
        }

        public function suspend(array $attributes): array
        {
            return [];
        }

        public function terminate(array $attributes): array
        {
            return [];
        }
    };

    $registry->register($driver);

    expect($registry->resolve('CPANEL'))->toBe($driver)
        ->and($registry->keys())->toBe(['cpanel'])
        ->and(fn () => $registry->register($driver))
        ->toThrow(InvalidArgumentException::class, 'Hosting driver keys must be non-empty and unique.');
});
