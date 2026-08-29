<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Liberu\Billing\Domains\Actions\CreateDomain;
use Liberu\Billing\Domains\Actions\UpdateDomain;
use Liberu\Billing\Domains\Actions\UpsertDnsRecord;
use Liberu\Billing\Domains\Contracts\RegistrarClient;
use Liberu\Billing\Domains\Models\DomainTld;
use Liberu\Billing\Domains\Services\DomainPricingService;
use Liberu\Billing\Domains\Services\RegistrarManager;

uses(RefreshDatabase::class);

it('prices supported TLDs with configured markup and preserves hosting bundles', function () {
    DomainTld::query()->create(['name' => '.com', 'registrar_cost' => 10, 'base_price' => 10, 'markup_type' => 'percentage', 'markup_value' => 25, 'enabled' => true]);

    $pricing = app(DomainPricingService::class);

    expect($pricing->calculateDomainPrice('Example.COM'))->toBe(12.5)
        ->and($pricing->priceForDomain(12.5, true))->toBe(0.0)
        ->and($pricing->priceForDomain(12.5, false))->toBe(12.5);
});

it('rejects unsupported domain suffixes', function () {
    expect(fn () => app(DomainPricingService::class)->calculateDomainPrice('example.invalid'))
        ->toThrow(InvalidArgumentException::class);
});

it('only writes DNS records for domains owned by the current team', function () {
    $domain = app(CreateDomain::class)->handle(20, ['name' => 'example.com']);

    expect(fn () => app(UpsertDnsRecord::class)->execute(10, [
        'domain_id' => $domain->id,
        'type' => 'A',
        'host' => '@',
        'value' => '192.0.2.10',
    ]))->toThrow(InvalidArgumentException::class);

    $record = app(UpsertDnsRecord::class)->execute(20, [
        'domain_id' => $domain->id,
        'type' => 'A',
        'host' => ' @ ',
        'value' => ' 192.0.2.10 ',
    ]);

    expect($record->host)->toBe('@')->and($record->value)->toBe('192.0.2.10');
});

it('synchronizes registrar TLD costs with the configured markup', function () {
    app(RegistrarManager::class)->register('test', new class() implements RegistrarClient
    {
        public function registerDomain(string $domainName, mixed $customerId): ?array
        {
            return null;
        }

        public function renewDomain(string $domainName, int $period): ?array
        {
            return null;
        }

        public function transferDomain(string $domainName, string $authCode, mixed $customerId): ?array
        {
            return null;
        }

        public function checkAvailability(string $domainName): bool
        {
            return true;
        }

        public function getAvailableTlds(): array
        {
            return ['COM', '.net'];
        }

        public function getDomainPrice(string $tld): float
        {
            return $tld === '.com' ? 10.0 : 8.0;
        }

        public function getDnsRecords(string $domainName): array
        {
            return [];
        }

        public function addDnsRecord(string $domainName, array $record): bool
        {
            return true;
        }

        public function deleteDnsRecord(string $domainName, string $recordId): bool
        {
            return true;
        }

        public function getWhoisContacts(string $domainName): array
        {
            return [];
        }

        public function updateWhoisContacts(string $domainName, array $contacts): bool
        {
            return true;
        }
    });

    expect(app(DomainPricingService::class)->syncTlds('test', 20))->toBe(2)
        ->and(DomainTld::query()->where('name', '.com')->value('markup_value'))->toBe('20.0000')
        ->and(DomainTld::query()->where('name', '.net')->value('registrar_cost'))->toBe('8.0000');
});

it('updates the persisted domain row instead of a stale model instance', function (): void {
    $domain = app(CreateDomain::class)->handle(10, ['name' => 'example.com']);
    $domain->refresh();
    app(UpdateDomain::class)->handle($domain, ['status' => 'registered']);

    $updated = app(UpdateDomain::class)->handle($domain, ['name' => 'new-example.com']);

    expect($updated->name)->toBe('new-example.com')
        ->and($updated->status)->toBe('registered');
});
