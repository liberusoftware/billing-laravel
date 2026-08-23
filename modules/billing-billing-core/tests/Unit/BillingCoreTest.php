<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Liberu\Billing\Core\ValueObjects\BillingAmount;
use Liberu\Billing\Core\ValueObjects\BillingPeriod;

it('calculates a monthly period inclusively', function (): void {
    $period = BillingPeriod::monthly(CarbonImmutable::parse('2026-08-01 00:00:00'));

    expect($period->endsAt->toDateTimeString())->toBe('2026-08-31 23:59:59')
        ->and($period->contains(CarbonImmutable::parse('2026-08-31 23:59:59')))->toBeTrue();
});

it('adds amounts without floating point arithmetic', function (): void {
    $total = (new BillingAmount(1999, 'USD'))->add(new BillingAmount(1, 'USD'));

    expect($total->minorUnits)->toBe(2000);
});

it('rejects mixed currencies', function (): void {
    expect(fn () => (new BillingAmount(100, 'USD'))->add(new BillingAmount(100, 'EUR')))
        ->toThrow(InvalidArgumentException::class);
});
