<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Services\RegistrarManager;

final readonly class RenewDomain
{
    public function __construct(private DatabaseManager $database, private RegistrarManager $registrars) {}

    public function execute(Domain $domain, int $period = 1): Domain
    {
        if ($period < 1 || $period > 10) {
            throw new \InvalidArgumentException('The renewal period must be between one and ten years.');
        }
        $result = $this->registrars->client((string) $domain->registrar)->renewDomain($domain->name, $period);
        if ($result === null) {
            throw new \RuntimeException('The registrar rejected the domain renewal.');
        }

        return $this->database->transaction(function () use ($domain, $result): Domain {
            $domain->update(['status' => 'registered', 'expires_at' => $result['new_expiration_date'] ?? null]);

            return $domain->refresh();
        });
    }
}
