<?php

declare(strict_types=1);

use Liberu\Billing\Communications\Actions\CreateCommunicationService;

it('rejects a missing team or name', function (): void {
    expect(fn () => (new CreateCommunicationService())->handle(0, []))
        ->toThrow(InvalidArgumentException::class);
});
