<?php

declare(strict_types=1);

use Liberu\Billing\CustomerPortal\Actions\CreatePortalRequest;

it('rejects a missing team or name', function (): void {
    expect(fn () => (new CreatePortalRequest())->handle(0, []))
        ->toThrow(InvalidArgumentException::class);
});
