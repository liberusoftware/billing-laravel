<?php

declare(strict_types=1);

use Liberu\Billing\Hosting\Actions\TransitionHostingAccount;
use Liberu\Billing\Hosting\Models\HostingAccount;

it('rejects unsupported hosting account lifecycle states before persistence', function (): void {
    expect(fn () => app(TransitionHostingAccount::class)->handle(new HostingAccount(), 'unknown'))->toThrow(InvalidArgumentException::class);
});
