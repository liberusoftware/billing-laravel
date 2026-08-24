<?php

declare(strict_types=1);

use Liberu\Billing\Reporting\Actions\CreateMetricSnapshot;

it('rejects a missing team or name', function (): void {
    expect(fn () => (new CreateMetricSnapshot())->handle(0, []))
        ->toThrow(InvalidArgumentException::class);
});
