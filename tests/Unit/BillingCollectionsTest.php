<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Collections\Actions\OpenCollectionCase;
use Liberu\Billing\Collections\Actions\ApplyCreditControl;
use Liberu\Billing\Collections\Actions\PromisePayment;
use Liberu\Billing\Collections\Actions\RecoverCollectionCase;
use Liberu\Billing\Collections\Actions\ScheduleDunning;
use Liberu\Billing\Collections\Actions\ScheduleReminder;
use Liberu\Billing\Collections\Actions\SuspendCollectionCase;
use Liberu\Billing\Collections\Enums\CollectionStatus;

uses(RefreshDatabase::class);

it('supports collection promises, suspension, and recovery', function () {
    $case = app(OpenCollectionCase::class)->execute(['team_id' => 10, 'amount_minor' => 1200, 'currency' => 'usd']);
    $case = app(PromisePayment::class)->execute($case, now()->addDays(7));
    expect($case->status)->toBe(CollectionStatus::Promised);
    $case = app(SuspendCollectionCase::class)->execute($case, 'promise missed');
    $case = app(RecoverCollectionCase::class)->execute($case);
    expect($case->status)->toBe(CollectionStatus::Recovered);
});

it('rejects invalid collection amounts', function () {
    expect(fn () => app(OpenCollectionCase::class)->execute(['amount_minor' => 0, 'currency' => 'USD']))
        ->toThrow(InvalidArgumentException::class);
});

it('records dunning, reminder, and credit-control decisions', function () {
    $case = app(OpenCollectionCase::class)->execute(['team_id' => 10, 'amount_minor' => 1200, 'currency' => 'USD']);
    $nextAction = now()->addDays(3);

    $case = app(ScheduleDunning::class)->execute($case, $nextAction);
    expect($case->type)->toBe('dunning')
        ->and($case->next_action_at->diffInSeconds($nextAction))->toBeLessThanOrEqual(1);

    $case = app(ScheduleReminder::class)->execute($case, $nextAction->addDay());
    expect($case->type)->toBe('reminder');

    $case = app(ApplyCreditControl::class)->execute($case, 'suspend-service', 'Repeated non-payment');
    expect($case->type)->toBe('credit_control')
        ->and($case->metadata['credit_control_level'])->toBe('suspend-service')
        ->and($case->reason)->toBe('Repeated non-payment');
});
