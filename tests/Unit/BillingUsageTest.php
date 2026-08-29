<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Usage\Actions\CorrectUsage;
use Liberu\Billing\Usage\Actions\DefineMeter;
use Liberu\Billing\Usage\Actions\IngestUsage;
use Liberu\Billing\Usage\Actions\RateUsage;
use Liberu\Billing\Usage\Queries\AggregateUsage;

uses(RefreshDatabase::class);

it('defines meters, deduplicates usage, aggregates, and corrects records', function () {
    $meter = app(DefineMeter::class)->execute(['team_id' => 10, 'code' => 'api_calls', 'currency' => 'USD', 'unit_price_minor' => 5]);
    $record = app(IngestUsage::class)->execute($meter, ['event_key' => 'evt-1', 'quantity' => 3]);
    $duplicate = app(IngestUsage::class)->execute($meter, ['event_key' => 'evt-1', 'quantity' => 99]);
    app(CorrectUsage::class)->execute($record, -1, 'correction-1');
    $aggregate = app(AggregateUsage::class)->execute($meter->getKey());

    expect($duplicate->is($record))->toBeTrue()->and((float) $aggregate->quantity)->toBe(2.0)->and((int) $aggregate->amount_minor)->toBe(10);
});

it('rejects invalid usage events', function () {
    expect(fn () => app(DefineMeter::class)->execute(['code' => '', 'currency' => 'USD', 'unit_price_minor' => 1]))
        ->toThrow(InvalidArgumentException::class);
});

it('normalizes meter identifiers and rejects invalid thresholds', function () {
    $meter = app(DefineMeter::class)->execute([
        'team_id' => 10,
        'code' => ' API.Calls ',
        'name' => ' API calls ',
        'unit' => ' request ',
        'currency' => 'usd',
        'unit_price_minor' => 5,
        'threshold' => '100.5',
    ]);

    expect($meter->code)->toBe('api.calls')
        ->and($meter->name)->toBe('API calls')
        ->and($meter->unit)->toBe('request')
        ->and((float) $meter->threshold)->toBe(100.5);

    expect(fn () => app(DefineMeter::class)->execute([
        'code' => 'requests', 'currency' => 'USD', 'unit_price_minor' => 1, 'threshold' => -1,
    ]))->toThrow(InvalidArgumentException::class);
});

it('rates usage progressively across sorted tiers', function () {
    $meter = app(DefineMeter::class)->execute([
        'team_id' => 10,
        'code' => 'bandwidth',
        'currency' => 'USD',
        'unit_price_minor' => 10,
        'metadata' => ['tiers' => [
            ['up_to' => 20, 'unit_price_minor' => 4],
            ['up_to' => 10, 'unit_price_minor' => 5],
        ]],
    ]);

    expect(app(RateUsage::class)->execute($meter, 25))->toBe(140);

    $meter->update(['metadata' => ['tiers' => [['up_to' => 0, 'unit_price_minor' => 1]]]]);
    expect(fn () => app(RateUsage::class)->execute($meter, 1))
        ->toThrow(InvalidArgumentException::class);
});
