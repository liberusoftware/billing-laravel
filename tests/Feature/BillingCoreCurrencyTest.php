<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Core\Actions\ConvertCurrency;
use Liberu\Billing\Core\Models\BillingCurrency;

uses(RefreshDatabase::class);

it('converts currencies through team-scoped enabled rates', function (): void {
    BillingCurrency::query()->create(['team_id' => 10, 'code' => 'USD', 'name' => 'US Dollar', 'decimal_places' => 2, 'enabled' => true, 'exchange_rate' => 1]);
    BillingCurrency::query()->create(['team_id' => 10, 'code' => 'EUR', 'name' => 'Euro', 'decimal_places' => 2, 'enabled' => true, 'exchange_rate' => 0.9]);

    expect(app(ConvertCurrency::class)->execute(10, 10, 'USD', 'EUR'))->toMatchArray(['amount' => 9.0, 'from' => 'USD', 'to' => 'EUR']);
});

it('rejects disabled or unavailable currencies', function (): void {
    BillingCurrency::query()->create(['team_id' => 10, 'code' => 'USD', 'name' => 'US Dollar', 'decimal_places' => 2, 'enabled' => false, 'exchange_rate' => 1]);

    expect(fn () => app(ConvertCurrency::class)->execute(10, 10, 'USD', 'USD'))
        ->toThrow(InvalidArgumentException::class, 'Currency is unavailable: USD.');
});
