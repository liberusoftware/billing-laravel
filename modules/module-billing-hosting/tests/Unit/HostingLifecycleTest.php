<?php

declare(strict_types=1);

use Liberu\Billing\Hosting\Actions\TransitionHostingAccount;
it('rejects unsupported hosting account lifecycle states before persistence', function (): void {
    expect(fn () => app(TransitionHostingAccount::class)->handle(new Liberu\Billing\Hosting\Models\HostingAccount(), 'unknown'))->toThrow(InvalidArgumentException::class);
});
