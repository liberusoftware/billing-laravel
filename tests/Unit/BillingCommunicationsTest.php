<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Communications\Actions\TransitionCommunicationNumber;
use Liberu\Billing\Communications\Models\CommunicationNumber;

uses(RefreshDatabase::class);

it('transitions communication numbers through validated lifecycle states', function () {
    $number = CommunicationNumber::query()->create(['team_id' => 10, 'number' => '+12025550100', 'type' => 'phone', 'status' => 'available']);

    $updated = app(TransitionCommunicationNumber::class)->handle($number, 'suspended');

    expect($updated->status)->toBe('suspended');
});

it('rejects unknown communication number states', function () {
    $number = CommunicationNumber::query()->create(['team_id' => 10, 'number' => '+12025550101', 'type' => 'phone', 'status' => 'available']);

    expect(fn () => app(TransitionCommunicationNumber::class)->handle($number, 'unknown'))
        ->toThrow(InvalidArgumentException::class);
});
