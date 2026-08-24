<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Services\RegistrarManager;

final readonly class RegisterDomain
{
    public function __construct(private DatabaseManager $database, private RegistrarManager $registrars) {}

    public function execute(Domain $domain, mixed $customerId): Domain
    {
        $result = $this->registrars->client((string) $domain->registrar)->registerDomain($domain->name, $customerId);
        if ($result === null) {
            throw new \RuntimeException('The registrar rejected the domain registration.');
        }

        return $this->database->transaction(function () use ($domain, $result): Domain {
            $domain->update(['status' => 'registered', 'registered_at' => now(), 'expires_at' => $result['expiration_date'] ?? null]);

            return $domain->refresh();
        });
    }
}
