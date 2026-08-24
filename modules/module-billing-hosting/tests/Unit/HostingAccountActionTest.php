<?php

declare(strict_types=1);

use Liberu\Billing\Hosting\Actions\CreateHostingAccount;

it('rejects a missing team or name', function (): void {
    expect(fn () => (new CreateHostingAccount())->handle(0, []))
        ->toThrow(InvalidArgumentException::class);
});
