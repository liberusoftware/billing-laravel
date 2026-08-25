<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Services;

use InvalidArgumentException;
use Liberu\Billing\Domains\Models\DomainTld;

final class DomainPricingService
{
    public function __construct(private RegistrarManager $registrars) {}

    public function priceForDomain(float $basePrice, bool $bundledWithHosting): float
    {
        return $bundledWithHosting ? 0.0 : round(max(0, $basePrice), 2);
    }

    public function calculateDomainPrice(string $domainName): float
    {
        $tld = $this->tldFromDomain($domainName);
        $record = DomainTld::query()->where('name', $tld)->where('enabled', true)->first();
        if ($record === null) {
            throw new InvalidArgumentException("TLD not supported: {$tld}");
        }

        return $record->calculatePrice();
    }

    public function syncTlds(string $registrar, float $markupValue = 10): int
    {
        if ($markupValue < 0) {
            throw new InvalidArgumentException('TLD markup cannot be negative.');
        }

        $client = $this->registrars->client($registrar);
        $count = 0;
        foreach ($client->getAvailableTlds() as $name) {
            $tld = '.'.ltrim(strtolower(trim((string) $name)), '.');
            if ($tld === '.') {
                continue;
            }
            $cost = round((float) $client->getDomainPrice($tld), 4);
            DomainTld::query()->updateOrCreate(['name' => $tld], [
                'registrar_cost' => $cost,
                'base_price' => $cost,
                'markup_type' => 'percentage',
                'markup_value' => $markupValue,
                'enabled' => true,
            ]);
            $count++;
        }

        return $count;
    }

    private function tldFromDomain(string $domainName): string
    {
        $name = strtolower(rtrim(trim($domainName), '.'));
        $position = strrpos($name, '.');
        if ($position === false || $position === strlen($name) - 1) {
            throw new InvalidArgumentException('A fully qualified domain name is required.');
        }

        return substr($name, $position);
    }
}
