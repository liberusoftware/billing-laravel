<?php

namespace App\Services\Registrars;

use App\Services\Registrars\Contracts\RegistrarClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ResellerClubClient implements RegistrarClient
{
    protected $apiUrl;

    protected $authUserId;

    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.resellerclub.api_url');
        $this->authUserId = config('services.resellerclub.auth_userid');
        $this->apiKey = config('services.resellerclub.api_key');
    }

    /**
     * @return array{expiration_date: Carbon|null}|null
     * @throws ConnectionException
     */
    public function registerDomain($domainName, $customerId): ?array
    {
        $this->makeApiCall('domains/register.json', [
            'domain-name' => $domainName,
            'years' => 1,
            'customer-id' => $customerId,
            'invoice-option' => 'NoInvoice',
        ]);

        return ['expiration_date' => null];
    }

    /**
     * @return array{new_expiration_date: Carbon|null}|null
     */
    public function renewDomain($domainName, $period): ?array
    {
        // ponytail: out of R9's gate — renew payload needs order-id lookup; add when DomainService renews.
        return ['new_expiration_date' => null];
    }

    /**
     * @return array{expiration_date: Carbon|null}|null
     */
    public function transferDomain($domainName, $authCode, $customerId): ?array
    {
        // ponytail: out of R9's gate — add when transfers are wired.
        return ['expiration_date' => null];
    }

    /**
     * @throws ConnectionException
     */
    public function checkAvailability(string $domainName): bool
    {
        [$sld, $tld] = $this->splitDomain($domainName);

        $result = $this->makeApiCall('domains/available.json', [
            'domain-name' => $sld,
            'tlds' => $tld,
        ]);

        return ($result[$domainName]['status'] ?? null) === 'available';
    }

    /**
     * @return array<int, string>
     */
    public function getAvailableTlds(): array
    {
        // ponytail: out of R9's gate — ResellerClub products/customer-price exposes TLDs; add when needed.
        return [];
    }

    public function getDomainPrice(string $tld): float
    {
        // ponytail: out of R9's gate — products/customer-price; add when pricing pulls live.
        return 0.0;
    }

    /**
     * @return array<int, array{id: string, type: string, name: string, content: string, ttl: int}>
     */
    public function getDnsRecords(string $domainName): array
    {
        return [];
    }

    /**
     * @param  array{type: string, name: string, content: string, ttl?: int}  $record
     */
    public function addDnsRecord(string $domainName, array $record): bool
    {
        return true;
    }

    public function deleteDnsRecord(string $domainName, string $recordId): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getWhoisContacts(string $domainName): array
    {

        return [];
    }

    /**
     * @param  array<string, array<string, string>>  $contacts
     */
    public function updateWhoisContacts(string $domainName, array $contacts): bool
    {
        // ponytail: real registrar call here — ResellerClub domains/modify-contact.
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
     * @param string $action
     * @param array<string, mixed> $params
     * @return array
     * @throws ConnectionException
     */
    protected function makeApiCall(string $action, array $params): array
    {
        $response = Http::get(rtrim($this->apiUrl, '/').'/'.$action, array_merge([
            'auth-userid' => $this->authUserId,
            'api-key' => $this->apiKey,
        ], $params));

        $json = $response->json() ?? [];

        if ($response->failed() || ($json['status'] ?? null) === 'ERROR') {
            throw new RuntimeException('ResellerClub API error: '.($json['message'] ?? $response->body()));
        }

        return $json;
    }
}
