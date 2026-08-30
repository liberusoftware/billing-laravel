<?php

declare(strict_types=1);

namespace Liberu\Billing\ResellerClub;

use Liberu\Billing\Domains\Contracts\RegistrarClient;
use Liberu\Billing\Domains\Support\AbstractRegistrarClient;
use RuntimeException;

final class ResellerClubRegistrar extends AbstractRegistrarClient implements RegistrarClient
{
    public function registerDomain(string $domainName, mixed $customerId): ?array
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->endpoint('domains/register', ['domain-name' => $sld, 'tld' => $tld, 'customer-id' => $customerId, 'years' => 1]);
        $this->assertSuccess($data);

        return ['expiration_date' => $this->dateValue($data['endtime'] ?? $data['expiration-date'] ?? null)];
    }

    public function renewDomain(string $domainName, int $period): ?array
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->endpoint('domains/renew', ['domain-name' => $sld, 'tld' => $tld, 'years' => $period]);
        $this->assertSuccess($data);

        return ['new_expiration_date' => $this->dateValue($data['endtime'] ?? $data['expiration-date'] ?? null)];
    }

    public function transferDomain(string $domainName, string $authCode, mixed $customerId): ?array
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->endpoint('domains/transfer', ['domain-name' => $sld, 'tld' => $tld, 'auth-code' => $authCode, 'customer-id' => $customerId]);
        $this->assertSuccess($data);

        return ['expiration_date' => $this->dateValue($data['endtime'] ?? $data['expiration-date'] ?? null)];
    }

    public function checkAvailability(string $domainName): bool
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->endpoint('domains/available', ['domain-name' => $sld, 'tlds' => $tld]);

        return strtolower((string) ($data[$tld] ?? $data['status'] ?? '')) === 'available' || (string) ($data[$tld] ?? '') === '1';
    }

    public function getAvailableTlds(): array
    {
        $data = $this->endpoint('products/tlds');
        $tlds = $data['tlds'] ?? $data;
        if (! is_array($tlds)) {
            return [];
        }

        return array_values(array_filter(array_map(fn (mixed $tld): string => '.'.ltrim(strtolower((string) (is_array($tld) ? ($tld['tld'] ?? '') : $tld)), '.'), $tlds)));
    }

    public function getDomainPrice(string $tld): float
    {
        $data = $this->endpoint('domains/order', ['tld' => ltrim($tld, '.')]);

        return (float) ($data['cost'] ?? $data['sellingprice'] ?? $data['price'] ?? 0);
    }

    public function getDnsRecords(string $domainName): array
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->endpoint('domains/details', ['domain-name' => $sld, 'tld' => $tld]);
        $records = $data['dns-records'] ?? $data['records'] ?? [];

        return is_array($records) ? array_values(array_map(fn (mixed $record): array => ['id' => (string) data_get($record, 'id', ''), 'type' => (string) data_get($record, 'type', ''), 'name' => (string) data_get($record, 'name', data_get($record, 'host', '')), 'content' => (string) data_get($record, 'content', data_get($record, 'value', '')), 'ttl' => (int) data_get($record, 'ttl', 3600)], $records)) : [];
    }

    public function addDnsRecord(string $domainName, array $record): bool
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $this->assertSuccess($this->endpoint('domains/add-dns-record', ['domain-name' => $sld, 'tld' => $tld, 'type' => $record['type'], 'host' => $record['name'], 'value' => $record['content'], 'ttl' => $record['ttl'] ?? 3600]));

        return true;
    }

    public function deleteDnsRecord(string $domainName, string $recordId): bool
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $this->assertSuccess($this->endpoint('domains/delete-dns-record', ['domain-name' => $sld, 'tld' => $tld, 'record-id' => $recordId]));

        return true;
    }

    public function getWhoisContacts(string $domainName): array
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $data = $this->endpoint('domains/details', ['domain-name' => $sld, 'tld' => $tld]);

        return is_array($data['contacts'] ?? null) ? $data['contacts'] : [];
    }

    public function updateWhoisContacts(string $domainName, array $contacts): bool
    {
        [$sld, $tld] = $this->domainParts($domainName);
        $this->assertSuccess($this->endpoint('domains/modify-contact', ['domain-name' => $sld, 'tld' => $tld, 'contacts' => $contacts]));

        return true;
    }

    private function endpoint(string $path, array $parameters = []): array
    {
        $query = ['auth-userid' => $this->configValue('services.resellerclub.auth_userid'), 'api-key' => $this->configValue('services.resellerclub.api_key'), 'reseller-id' => $this->configValue('services.resellerclub.reseller_id'), 'version' => '3.0', 'response-format' => 'json'];

        return $this->get(rtrim((string) config('services.resellerclub.base_url', 'https://httpapi.com/api'), '/').'/'.ltrim($path, '/'), $query + $parameters);
    }

    private function assertSuccess(array $data): void
    {
        $status = strtolower((string) ($data['status'] ?? 'success'));
        if (in_array($status, ['failure', 'failed', 'error'], true) || isset($data['error'])) {
            throw new RuntimeException((string) ($data['message'] ?? $data['error'] ?? 'ResellerClub rejected the operation.'));
        }
    }
}
