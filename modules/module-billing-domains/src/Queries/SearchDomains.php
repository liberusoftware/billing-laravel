<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Queries;

use Illuminate\Support\Facades\Cache;
use Liberu\Billing\Domains\Services\RegistrarManager;

final readonly class SearchDomains
{
    public function __construct(private RegistrarManager $registrars) {}

    /** @return array{domain:string,available:bool,price:float|null} */
    public function execute(string $domain, string $registrar, array $suggestionTlds = ['com', 'net', 'org']): array
    {
        $domain = strtolower(rtrim(trim($domain), '.'));
        if (! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new \InvalidArgumentException('The domain name is invalid.');
        }
        $client = $this->registrars->client($registrar);
        $lookup = fn (string $candidate): array => $this->lookup($client, $candidate);
        [$sld] = explode('.', $domain, 2);
        $suggestions = [];
        foreach (array_slice(array_values(array_unique($suggestionTlds)), 0, 3) as $tld) {
            $candidate = $sld.'.'.ltrim((string) $tld, '.');
            if ($candidate !== $domain) {
                $suggestions[] = $lookup($candidate);
            }
        }

        return $lookup($domain) + ['suggestions' => $suggestions];
    }

    /** @return array{domain:string,available:bool,price:float|null} */
    private function lookup(object $client, string $domain): array
    {
        $available = Cache::remember('billing-domain-availability:'.$domain, now()->addMinutes(10), fn (): bool => $client->checkAvailability($domain));
        $tld = '.'.ltrim((string) strrchr($domain, '.'), '.');

        return ['domain' => $domain, 'available' => $available, 'price' => $available ? round($client->getDomainPrice($tld), 2) : null];
    }
}
