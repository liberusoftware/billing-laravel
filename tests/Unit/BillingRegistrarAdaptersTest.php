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
            ->push(['RRPCode' => '210'])
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

it('reads Enom XML availability without treating generic success as availability', function (string $code, bool $available): void {
    config()->set('services.enom', ['username' => 'user', 'password' => 'secret', 'base_url' => 'https://enom.test/interface.asp']);
    Http::preventStrayRequests();
    Http::fake(['https://enom.test/*' => Http::response('<interface-response><ErrCount>0</ErrCount><RRPCode>'.$code.'</RRPCode></interface-response>', 200, ['Content-Type' => 'text/xml'])]);

    expect(app(EnomRegistrar::class)->checkAvailability('example.com'))->toBe($available);
})->with(['available' => ['210', true], 'taken' => ['211', false], 'generic success' => ['200', false]]);

it('rejects Enom operation errors even without a CommandResponse field', function (array $response): void {
    config()->set('services.enom', ['username' => 'user', 'password' => 'secret', 'base_url' => 'https://enom.test/interface.asp']);
    Http::preventStrayRequests();
    Http::fake(['https://enom.test/*' => Http::response($response)]);

    expect(fn () => app(EnomRegistrar::class)->registerDomain('example.com', 7))
        ->toThrow(RuntimeException::class, 'Enom rejected the operation.');
})->with([
    'error count' => [['ErrCount' => '1', 'Err1' => 'secret provider details']],
    'error field' => [['Err1' => 'secret provider details']],
    'registry rejection' => [['RRPCode' => '541']],
]);

it('does not replay registrar mutations after an ambiguous server failure', function (string $registrar): void {
    config()->set('services.enom', ['username' => 'user', 'password' => 'secret', 'base_url' => 'https://enom.test/interface.asp']);
    config()->set('services.resellerclub', ['auth_userid' => '123', 'api_key' => 'secret', 'reseller_id' => '456', 'base_url' => 'https://reseller.test/api']);
    Http::preventStrayRequests();
    Http::fake(['*' => Http::sequence()->push('upstream failure with secret', 503)->push(['RRPCode' => '200', 'status' => 'Success'])]);

    expect(fn () => app($registrar)->registerDomain('example.com', 7))
        ->toThrow(RuntimeException::class, 'Registrar API returned HTTP 503.');
    Http::assertSentCount(1);
})->with([EnomRegistrar::class, ResellerClubRegistrar::class]);

it('rejects malformed registrar responses', function (string $body): void {
    config()->set('services.enom', ['username' => 'user', 'password' => 'secret', 'base_url' => 'https://enom.test/interface.asp']);
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response($body)]);

    expect(fn () => app(EnomRegistrar::class)->registerDomain('example.com', 7))
        ->toThrow(RuntimeException::class);
})->with(['', '<html>upstream error</html>', '<interface-response>', 'not a registrar response']);

it('extracts expiration dates from successful Enom XML operations', function (string $operation, string $key, array $arguments): void {
    config()->set('services.enom', ['username' => 'user', 'password' => 'secret', 'base_url' => 'https://enom.test/interface.asp']);
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response('<interface-response><ErrCount>0</ErrCount><RRPCode>200</RRPCode><ExpirationDate>2030-01-01</ExpirationDate></interface-response>')]);

    expect(app(EnomRegistrar::class)->{$operation}(...$arguments)[$key]->toDateString())->toBe('2030-01-01');
})->with([
    'registration' => ['registerDomain', 'expiration_date', ['example.com', 7]],
    'renewal' => ['renewDomain', 'new_expiration_date', ['example.com', 1]],
    'transfer' => ['transferDomain', 'expiration_date', ['example.com', 'AUTH', 7]],
]);

it('rejects XML entities without changing the caller libxml error mode', function (): void {
    config()->set('services.enom', ['username' => 'user', 'password' => 'secret', 'base_url' => 'https://enom.test/interface.asp']);
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response('<!DOCTYPE interface-response [<!ENTITY data SYSTEM "file:///etc/hostname">]><interface-response><RRPCode>200</RRPCode><ExpirationDate>&data;</ExpirationDate></interface-response>')]);
    $previous = libxml_use_internal_errors();

    expect(fn () => app(EnomRegistrar::class)->registerDomain('example.com', 7))
        ->toThrow(RuntimeException::class, 'Registrar API returned an invalid response.');
    expect(libxml_use_internal_errors())->toBe($previous);
});
