<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Core\Actions\CalculateTax;
use Liberu\Billing\Core\Actions\ConvertCurrency;
use Liberu\Billing\Core\Models\BillingCurrency;
use Liberu\Billing\Core\Models\BillingTaxProfile;

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

it('calculates exclusive and inclusive tax from an enabled jurisdiction profile', function (): void {
    BillingTaxProfile::query()->create(['team_id' => 10, 'name' => 'UK VAT', 'rate' => 20, 'jurisdiction' => 'GB', 'inclusive' => false, 'enabled' => true]);

    expect(app(CalculateTax::class)->execute(10, 100, 'GB'))->toMatchArray(['subtotal' => 100.0, 'tax' => 20.0, 'total' => 120.0]);

    BillingTaxProfile::query()->update(['inclusive' => true]);
    expect(app(CalculateTax::class)->execute(10, 120, 'GB'))->toMatchArray(['subtotal' => 100.0, 'tax' => 20.0, 'total' => 120.0]);
});
