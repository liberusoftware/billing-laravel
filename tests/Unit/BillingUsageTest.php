<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Usage\Actions\CorrectUsage;
use Liberu\Billing\Usage\Actions\DefineMeter;
use Liberu\Billing\Usage\Actions\IngestUsage;
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
