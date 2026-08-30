<?php

declare(strict_types=1);

namespace Liberu\Billing\Enom;

use Liberu\Billing\Domains\Contracts\RegistrarClient;
use Liberu\Billing\Domains\Support\AbstractRegistrarClient;
use RuntimeException;

final class EnomRegistrar extends AbstractRegistrarClient implements RegistrarClient
{
    public function registerDomain(string $domainName, mixed $customerId): ?array
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->command('Purchase', compact('sld', 'tld') + ['CustomerID' => $customerId]);
        $this->assertSuccess($data);

        return ['expiration_date' => $this->dateValue($data['ExpirationDate'] ?? $data['expiration_date'] ?? null)];
    }

    public function renewDomain(string $domainName, int $period): ?array
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->command('Renew', compact('sld', 'tld') + ['NumYears' => $period]);
        $this->assertSuccess($data);

        return ['new_expiration_date' => $this->dateValue($data['ExpirationDate'] ?? $data['NewExpirationDate'] ?? null)];
    }

    public function transferDomain(string $domainName, string $authCode, mixed $customerId): ?array
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->command('Transfer', compact('sld', 'tld') + ['TransferAuthInfo' => $authCode, 'CustomerID' => $customerId]);
        $this->assertSuccess($data);

        return ['expiration_date' => $this->dateValue($data['ExpirationDate'] ?? null)];
    }

    public function checkAvailability(string $domainName): bool
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->command('Check', compact('sld', 'tld'));

        return strtolower((string) ($data['RRPCode'] ?? $data['Available'] ?? $data['CommandResponse'] ?? '')) === 'available' || (string) ($data['RRPCode'] ?? '') === '200';
    }

    public function getAvailableTlds(): array
    {
        $data = $this->command('GetTLDList');
        $value = $data['TLD'] ?? $data['TLDs'] ?? [];

        return array_values(array_filter(array_map(fn (mixed $tld): string => '.'.ltrim(strtolower((string) $tld), '.'), is_array($value) ? $value : [$value])));
    }

    public function getDomainPrice(string $tld): float
    {
        $data = $this->command('GetRetailPrice', ['tld' => ltrim($tld, '.')]);

        return (float) ($data['RetailPrice'] ?? $data['Price'] ?? $data['price'] ?? 0);
    }

    public function getDnsRecords(string $domainName): array
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->command('GetDNSHostRecords', compact('sld', 'tld'));

        return $this->records($data);
    }

    public function addDnsRecord(string $domainName, array $record): bool
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $this->assertSuccess($this->command('AddDNSHost', compact('sld', 'tld') + ['HostName' => $record['name'], 'RecordType' => $record['type'], 'Address' => $record['content'], 'TTL' => $record['ttl'] ?? 3600]));

        return true;
    }

    public function deleteDnsRecord(string $domainName, string $recordId): bool
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $this->assertSuccess($this->command('DeleteDNSHost', compact('sld', 'tld') + ['HostID' => $recordId]));

        return true;
    }

    public function getWhoisContacts(string $domainName): array
    {
        [$sld, $tld] = $this->domainParts($domainName);

        return $this->command('GetContacts', compact('sld', 'tld'));
    }

    public function updateWhoisContacts(string $domainName, array $contacts): bool
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $this->assertSuccess($this->command('SetContacts', compact('sld', 'tld') + ['contacts' => $contacts]));

        return true;
    }

    private function command(string $command, array $parameters = []): array
    {
        $query = ['uid' => $this->configValue('services.enom.username'), 'pw' => $this->configValue('services.enom.password', config('services.enom.api_token')), 'command' => $command, 'responsetype' => 'XML'];
        $response = $this->get(rtrim((string) config('services.enom.base_url', 'https://reseller.enom.com/interface.asp'), '?'), $query + $parameters);

        return $response;
    }

    private function assertSuccess(array $data): void
    {
        $code = (string) ($data['RRPCode'] ?? $data['Err1'] ?? '200');
        if ($code !== '' && $code !== '200' && str_contains(strtolower((string) ($data['CommandResponse'] ?? '')), 'error')) {
            throw new RuntimeException((string) ($data['Err1'] ?? $data['CommandResponse'] ?? 'Enom rejected the operation.'));
        }
    }

    private function records(array $data): array
    {
        $records = $data['record'] ?? $data['Record'] ?? [];
        if (! is_array($records)) {
            return [];
        }

        return array_values(array_map(fn (mixed $record): array => ['id' => (string) data_get($record, 'id', data_get($record, 'HostID', '')), 'type' => (string) data_get($record, 'type', data_get($record, 'RecordType', '')), 'name' => (string) data_get($record, 'name', data_get($record, 'HostName', '')), 'content' => (string) data_get($record, 'content', data_get($record, 'Address', '')), 'ttl' => (int) data_get($record, 'ttl', 3600)], $records));
    }
}
