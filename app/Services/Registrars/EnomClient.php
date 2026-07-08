<?php

namespace App\Services\Registrars;

use App\Services\Registrars\Contracts\RegistrarClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class EnomClient implements RegistrarClient
{
    protected $apiUrl;

    protected $username;

    protected $password;

    public function __construct()
    {
        $this->apiUrl = config('services.enom.api_url');
        $this->username = config('services.enom.username');
        $this->password = config('services.enom.password');
    }

    /**
     * @return array{expiration_date: Carbon|null}|null
     */
    public function registerDomain($domainName, $customerId): ?array
    {
        [$sld, $tld] = $this->splitDomain($domainName);

        $this->makeApiCall('Purchase', [
            'SLD' => $sld,
            'TLD' => $tld,
            'customerid' => $customerId,
        ]);

        return ['expiration_date' => null];
    }

    /**
     * @return array{new_expiration_date: Carbon|null}|null
     */
    public function renewDomain($domainName, $period): ?array
    {
        [$sld, $tld] = $this->splitDomain($domainName);

        $this->makeApiCall('Extend', [
            'SLD' => $sld,
            'TLD' => $tld,
            'NumYears' => $period,
        ]);

        return ['new_expiration_date' => null];
    }

    /**
     * @return array{expiration_date: Carbon|null}|null
     */
    public function transferDomain($domainName, $authCode, $customerId): ?array
    {
        [$sld, $tld] = $this->splitDomain($domainName);

        $this->makeApiCall('TP_CreateOrder', [
            'SLD' => $sld,
            'TLD' => $tld,
            'AuthInfo' => $authCode,
            'customerid' => $customerId,
        ]);

        return ['expiration_date' => null];
    }

    public function checkAvailability(string $domainName): bool
    {
        [$sld, $tld] = $this->splitDomain($domainName);

        $xml = $this->makeApiCall('Check', ['SLD' => $sld, 'TLD' => $tld]);

        // eNom RRPCode 210 = available, 211 = taken.
        return (int) ($xml->RRPCode ?? 0) === 210;
    }

    /**
     * @return array<int, string>
     */
    public function getAvailableTlds(): array
    {
        $xml = $this->makeApiCall('GetTLDList', []);

        $tlds = [];
        foreach ($xml->tldlist->tld ?? [] as $tld) {
            $tlds[] = (string) $tld->tld;
        }

        return $tlds;
    }

    public function getDomainPrice(string $tld): float
    {
        $xml = $this->makeApiCall('PE_GetDomainPrice', ['TLD' => ltrim($tld, '.')]);

        return (float) ($xml->price ?? 0);
    }

    /**
     * Current registry expiration date for a domain (used by the sync command).
     */
    public function getDomainExpiration(string $domainName): ?Carbon
    {
        [$sld, $tld] = $this->splitDomain($domainName);
        $xml = $this->makeApiCall('GetDomainExp', ['SLD' => $sld, 'TLD' => $tld]);

        $date = trim((string) ($xml->ExpirationDate ?? ''));

        return $date !== '' ? Carbon::parse($date) : null;
    }

    /**
     * @return array<int, array{id: string, type: string, name: string, content: string, ttl: int}>
     */
    public function getDnsRecords(string $domainName): array
    {
        [$sld, $tld] = $this->splitDomain($domainName);
        $xml = $this->makeApiCall('GetHosts', ['SLD' => $sld, 'TLD' => $tld]);

        $records = [];
        foreach ($xml->host ?? [] as $host) {
            $records[] = [
                'id' => (string) ($host->HostID ?? ''),
                'type' => (string) ($host->RecordType ?? ''),
                'name' => (string) ($host->HostName ?? ''),
                'content' => (string) ($host->Address ?? ''),
                'ttl' => (int) ($host->TTL ?? 3600),
            ];
        }

        return $records;
    }

    /**
     * @param  array{type: string, name: string, content: string, ttl?: int}  $record
     */
    public function addDnsRecord(string $domainName, array $record): bool
    {
        [$sld, $tld] = $this->splitDomain($domainName);
        $this->makeApiCall('SetHosts', array_merge(['SLD' => $sld, 'TLD' => $tld], $record));

        return true;
    }

    public function deleteDnsRecord(string $domainName, string $recordId): bool
    {
        [$sld, $tld] = $this->splitDomain($domainName);
        $this->makeApiCall('SetHosts', ['SLD' => $sld, 'TLD' => $tld, 'DeleteHostId' => $recordId]);

        return true;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getWhoisContacts(string $domainName): array
    {
        [$sld, $tld] = $this->splitDomain($domainName);
        $xml = $this->makeApiCall('GetWhoisContact', ['SLD' => $sld, 'TLD' => $tld]);

        $contacts = [];
        foreach ($xml->contacts->contact ?? [] as $contact) {
            $type = (string) ($contact->ContactType ?? 'Registrant');
            $contacts[$type] = [
                'first_name' => (string) ($contact->FirstName ?? ''),
                'last_name' => (string) ($contact->LastName ?? ''),
                'email' => (string) ($contact->EmailAddress ?? ''),
            ];
        }

        return $contacts;
    }

    /**
     * @param  array<string, array<string, string>>  $contacts
     */
    public function updateWhoisContacts(string $domainName, array $contacts): bool
    {
        [$sld, $tld] = $this->splitDomain($domainName);
        $this->makeApiCall('Contacts', ['SLD' => $sld, 'TLD' => $tld]);

        return true;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function splitDomain(string $domainName): array
    {
        $parts = explode('.', $domainName, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function makeApiCall(string $command, array $params): SimpleXMLElement
    {
        $response = Http::get($this->apiUrl, array_merge([
            'command' => $command,
            'uid' => $this->username,
            'pw' => $this->password,
            'responsetype' => 'xml',
        ], $params));

        $xml = simplexml_load_string($response->body() ?: '<interface-response/>');

        if ($xml === false) {
            throw new RuntimeException('Invalid eNom API response.');
        }

        if ((int) ($xml->ErrCount ?? 0) > 0) {
            throw new RuntimeException('eNom API error: '.trim((string) ($xml->errors->Err1 ?? 'unknown error')));
        }

        return $xml;
    }
}
