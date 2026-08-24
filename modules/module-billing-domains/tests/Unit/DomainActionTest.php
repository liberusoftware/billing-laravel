<?php

declare(strict_types=1);

use Liberu\Billing\Domains\Actions\CreateDomain;

it('rejects a missing team or name', function (): void {
    expect(fn () => (new CreateDomain())->handle(0, []))
        ->toThrow(InvalidArgumentException::class);
});
