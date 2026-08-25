<?php

declare(strict_types=1);

use Liberu\Billing\Isp\Actions\CreateAccessService;

it('rejects a missing team or name', function (): void {
    expect(fn () => (new CreateAccessService())->handle(0, []))
        ->toThrow(InvalidArgumentException::class);
});
