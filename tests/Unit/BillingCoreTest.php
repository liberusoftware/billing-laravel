<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Core\Actions\CreateBillingAccount;
use Liberu\Billing\Core\Actions\CreateBillingRecord;
use Liberu\Billing\Core\Actions\TransitionBillingAccount;
use Liberu\Billing\Core\Actions\UpdateBillingAccount;
use Liberu\Billing\Core\Actions\UpdateBillingRecord;
use Liberu\Billing\Core\Enums\BillingAccountStatus;
use Liberu\Billing\Core\Models\BillingAccount;
use Liberu\Billing\Core\Models\BillingContact;

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

it('does not transition an account after its persisted state becomes closed', function (): void {
    $account = app(CreateBillingAccount::class)->execute(['team_id' => 10, 'name' => 'Stale', 'currency' => 'USD']);
    $account->refresh();
    BillingAccount::query()->whereKey($account->getKey())->update(['status' => BillingAccountStatus::Closed->value]);

    expect(fn () => app(TransitionBillingAccount::class)->execute($account, BillingAccountStatus::Active))
        ->toThrow(InvalidArgumentException::class, 'A closed billing account cannot be reopened.');
});

it('updates a locked billing core record from its persisted state', function (): void {
    $contact = app(CreateBillingRecord::class)->execute(BillingContact::class, [
        'team_id' => 10, 'name' => 'Original', 'email' => 'original@example.com',
    ]);
    $contact->refresh();

    $updated = app(UpdateBillingRecord::class)->execute($contact, ['name' => 'Updated']);

    expect($updated->name)->toBe('Updated')
        ->and($updated->email)->toBe('original@example.com');
});
