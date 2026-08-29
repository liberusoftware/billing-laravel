<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Collections\Actions\ApplyCreditControl;
use Liberu\Billing\Collections\Actions\OpenCollectionCase;
use Liberu\Billing\Collections\Actions\PromisePayment;
use Liberu\Billing\Collections\Actions\RecoverCollectionCase;
use Liberu\Billing\Collections\Actions\ScheduleDunning;
use Liberu\Billing\Collections\Actions\ScheduleReminder;
use Liberu\Billing\Collections\Actions\SuspendCollectionCase;
use Liberu\Billing\Collections\Actions\WriteOffCollectionCase;
use Liberu\Billing\Collections\Enums\CollectionStatus;
use Liberu\Billing\Collections\Models\CollectionCase;
use Liberu\Billing\Invoicing\Models\Invoice;

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

it('rejects unsupported collection case types', function (): void {
    expect(fn () => app(OpenCollectionCase::class)->execute([
        'amount_minor' => 100, 'currency' => 'USD', 'type' => 'unsupported',
    ]))->toThrow(InvalidArgumentException::class, 'Collection case type is invalid.');
});

it('rejects a collection case invoice owned by another team', function (): void {
    $invoice = Invoice::query()->create([
        'team_id' => 20, 'status' => 'draft', 'currency' => 'USD',
        'subtotal_minor' => 100, 'tax_minor' => 0, 'total_minor' => 100,
    ]);

    expect(fn () => app(OpenCollectionCase::class)->execute([
        'team_id' => 10, 'invoice_id' => $invoice->getKey(), 'amount_minor' => 100, 'currency' => 'USD',
    ]))->toThrow(InvalidArgumentException::class, 'Collection invoice reference is invalid.');
});

it('does not write off a case after its persisted state becomes recovered', function (): void {
    $case = app(OpenCollectionCase::class)->execute(['team_id' => 10, 'amount_minor' => 100, 'currency' => 'USD']);
    $case->refresh();
    CollectionCase::query()->whereKey($case->getKey())->update(['status' => CollectionStatus::Recovered->value]);

    expect(fn () => app(WriteOffCollectionCase::class)->execute($case, 'uncollectible'))
        ->toThrow(LogicException::class, 'This collection case cannot be written off.');
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

it('does not recover a case after its persisted state becomes terminal', function (): void {
    $case = app(OpenCollectionCase::class)->execute(['team_id' => 10, 'amount_minor' => 1200, 'currency' => 'USD']);
    $case->refresh();
    CollectionCase::query()->whereKey($case->getKey())->update(['status' => CollectionStatus::WrittenOff->value]);

    expect(fn () => app(RecoverCollectionCase::class)->execute($case))
        ->toThrow(LogicException::class, 'This collection case cannot be recovered.');
});
