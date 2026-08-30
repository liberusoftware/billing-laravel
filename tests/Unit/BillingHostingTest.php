<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Liberu\Billing\Hosting\Actions\CreateHostingAccount;
use Liberu\Billing\Hosting\Actions\CreateHostingCapability;
use Liberu\Billing\Hosting\Actions\TransitionHostingAccount;
use Liberu\Billing\Hosting\Actions\TransitionHostingCapability;
use Liberu\Billing\Hosting\Events\HostingAccountCreated;
use Liberu\Billing\Hosting\Events\HostingAccountStatusChanged;
use Liberu\Billing\Hosting\Events\HostingCapabilityCreated;
use Liberu\Billing\Hosting\Events\HostingCapabilityStatusChanged;

uses(RefreshDatabase::class);

it('emits after-commit events for hosting account and capability lifecycles', function (): void {
    Event::fake([
        HostingAccountCreated::class,
        HostingAccountStatusChanged::class,
        HostingCapabilityCreated::class,
        HostingCapabilityStatusChanged::class,
    ]);

    $account = app(CreateHostingAccount::class)->handle(10, ['name' => 'example-host']);
    $account = app(TransitionHostingAccount::class)->handle($account, 'suspended');
    $capability = app(CreateHostingCapability::class)->handle(10, [
        'hosting_account_id' => $account->getKey(),
        'type' => 'ssl',
        'name' => 'managed-tls',
    ]);
    app(TransitionHostingCapability::class)->handle($capability, 'active');

    Event::assertDispatched(HostingAccountCreated::class);
    Event::assertDispatched(HostingAccountStatusChanged::class);
    Event::assertDispatched(HostingCapabilityCreated::class);
    Event::assertDispatched(HostingCapabilityStatusChanged::class);
});
