<?php

use Illuminate\Support\Facades\Http;
use Liberu\Billing\Domains\Services\RegistrarManager;
use Liberu\Billing\Enom\EnomRegistrar;
use Liberu\Billing\ResellerClub\ResellerClubRegistrar;

it('supports Enom registration, renewal, transfer, availability, and pricing through its isolated adapter', function (): void {
    config()->set('services.enom', ['username' => 'enom-user', 'password' => 'enom-secret', 'base_url' => 'https://enom.test/interface.asp']);
    Http::fake([
        'https://enom.test/*' => Http::sequence()
            ->push(['RRPCode' => '200', 'ExpirationDate' => '2030-01-01'])
            ->push(['RRPCode' => '200', 'ExpirationDate' => '2031-01-01'])
            ->push(['RRPCode' => '200', 'ExpirationDate' => '2032-01-01'])
            ->push(['RRPCode' => '200'])
            ->push(['TLD' => ['COM', 'NET']])
            ->push(['RetailPrice' => '12.50']),
    ]);
    $registrar = app(EnomRegistrar::class);

    expect($registrar->registerDomain('Example.com', 7)['expiration_date']->toDateString())->toBe('2030-01-01')
        ->and($registrar->renewDomain('example.com', 1)['new_expiration_date']->toDateString())->toBe('2031-01-01')
        ->and($registrar->transferDomain('example.com', 'AUTH', 7)['expiration_date']->toDateString())->toBe('2032-01-01')
        ->and($registrar->checkAvailability('example.com'))->toBeTrue()
        ->and($registrar->getAvailableTlds())->toBe(['.com', '.net'])
        ->and($registrar->getDomainPrice('.com'))->toBe(12.5);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'https://enom.test/interface.asp'));
});

it('supports ResellerClub registration, renewal, transfer, availability, and pricing through its isolated adapter', function (): void {
    config()->set('services.resellerclub', ['auth_userid' => '123', 'api_key' => 'secret', 'reseller_id' => '456', 'base_url' => 'https://reseller.test/api']);
    Http::fake([
        'https://reseller.test/*' => Http::sequence()
            ->push(['status' => 'Success', 'endtime' => '2030-01-01'])
            ->push(['status' => 'Success', 'endtime' => '2031-01-01'])
            ->push(['status' => 'Success', 'endtime' => '2032-01-01'])
            ->push(['com' => 'available'])
            ->push(['tlds' => ['com', 'net']])
            ->push(['cost' => '9.95']),
    ]);
    $registrar = app(ResellerClubRegistrar::class);

    expect($registrar->registerDomain('Example.com', 7)['expiration_date']->toDateString())->toBe('2030-01-01')
        ->and($registrar->renewDomain('example.com', 1)['new_expiration_date']->toDateString())->toBe('2031-01-01')
        ->and($registrar->transferDomain('example.com', 'AUTH', 7)['expiration_date']->toDateString())->toBe('2032-01-01')
        ->and($registrar->checkAvailability('example.com'))->toBeTrue()
        ->and($registrar->getAvailableTlds())->toBe(['.com', '.net'])
        ->and($registrar->getDomainPrice('.com'))->toBe(9.95);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'https://reseller.test/api/domains/register') && $request->url() !== '' && str_contains($request->url(), 'auth-userid=123'));
});

it('registers both optional registrar adapters with the provider-neutral manager', function (): void {
    config()->set('services.enom', ['username' => 'u', 'password' => 'p']);
    config()->set('services.resellerclub', ['auth_userid' => 'u', 'api_key' => 'p', 'reseller_id' => 'r']);

    $manager = app(RegistrarManager::class);
    $manager->register('enom', app(EnomRegistrar::class));
    $manager->register('resellerclub', app(ResellerClubRegistrar::class));

    expect($manager->client('enom'))->toBeInstanceOf(EnomRegistrar::class)
        ->and($manager->client('resellerclub'))->toBeInstanceOf(ResellerClubRegistrar::class);
});
