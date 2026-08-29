<?php

use Liberu\Billing\Hosting\Actions\TransitionHostingAccount;
use Liberu\Billing\Hosting\Models\HostingAccount;

it('does not reactivate cancelled hosting accounts', function (): void {
    $account = HostingAccount::query()->create([
        'team_id' => 1,
        'name' => 'example-hosting',
        'status' => 'cancelled',
    ]);

    expect(fn () => app(TransitionHostingAccount::class)->handle($account, 'active'))
        ->toThrow(InvalidArgumentException::class, 'Cancelled hosting accounts cannot be reactivated.');
});
