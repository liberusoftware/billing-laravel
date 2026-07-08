<?php

declare(strict_types=1);

namespace App\Services\Registrars\Contracts;

use Illuminate\Support\Carbon;

interface RegistrarClient
{
    /**
     * @return array{expiration_date: Carbon|null}|null
     */
    public function registerDomain($domainName, $customerId): ?array;

    /**
     * @return array{new_expiration_date: Carbon|null}|null
     */
    public function renewDomain($domainName, $period): ?array;

    /**
     * @return array{expiration_date: Carbon|null}|null
     */
    public function transferDomain($domainName, $authCode, $customerId): ?array;

    public function checkAvailability(string $domainName): bool;

    /**
     * @return array<int, string>
     */
    public function getAvailableTlds(): array;

    public function getDomainPrice(string $tld): float;

    /**
     * @return array<int, array{id: string, type: string, name: string, content: string, ttl: int}>
     */
    public function getDnsRecords(string $domainName): array;

    /**
     * @param  array{type: string, name: string, content: string, ttl?: int}  $record
     */
    public function addDnsRecord(string $domainName, array $record): bool;

    public function deleteDnsRecord(string $domainName, string $recordId): bool;

    /**
     * @return array<string, array<string, string>>
     */
    public function getWhoisContacts(string $domainName): array;

    /**
     * @param  array<string, array<string, string>>  $contacts
     */
    public function updateWhoisContacts(string $domainName, array $contacts): bool;
}
