<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Core\Actions\CreateBillingAccount;
use Liberu\Billing\Core\Actions\TransitionBillingAccount;
use Liberu\Billing\Core\Actions\UpdateBillingAccount;
use Liberu\Billing\Core\Enums\BillingAccountStatus;

uses(RefreshDatabase::class);

it('updates and transitions a billing account while keeping normalized values', function () {
    $account = app(CreateBillingAccount::class)->execute(['team_id' => 10, 'name' => ' Old ', 'currency' => 'usd']);

    $updated = app(UpdateBillingAccount::class)->execute($account, ['name' => ' New ', 'currency' => 'eur']);
    $suspended = app(TransitionBillingAccount::class)->execute($updated, BillingAccountStatus::Suspended);

    expect($suspended->name)->toBe('New')
        ->and($suspended->currency)->toBe('EUR')
        ->and($suspended->status)->toBe(BillingAccountStatus::Suspended);
});

it('does not reopen a closed billing account', function () {
    $account = app(CreateBillingAccount::class)->execute(['team_id' => 10, 'name' => 'Closed', 'currency' => 'USD']);
    $closed = app(TransitionBillingAccount::class)->execute($account, BillingAccountStatus::Closed);

    expect(fn () => app(TransitionBillingAccount::class)->execute($closed, BillingAccountStatus::Active))
        ->toThrow(InvalidArgumentException::class);
});
