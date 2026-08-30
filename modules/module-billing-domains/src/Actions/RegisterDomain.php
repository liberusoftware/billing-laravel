<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Domains\Events\DomainRegistered;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Services\RegistrarManager;
use Liberu\Billing\Domains\Support\CustomerReference;

final readonly class RegisterDomain
{
    public function __construct(private DatabaseManager $database, private RegistrarManager $registrars) {}

    public function execute(Domain $domain, mixed $customerId): Domain
    {
        CustomerReference::assertBelongsToTeam($this->database, $customerId, $domain->team_id);
        $result = $this->registrars->client((string) $domain->registrar)->registerDomain($domain->name, $customerId);
        if ($result === null) {
            throw new \RuntimeException('The registrar rejected the domain registration.');
        }

        return $this->database->transaction(function () use ($domain, $result): Domain {
            $domain->update(['status' => 'registered', 'registered_at' => now(), 'expires_at' => $result['expiration_date'] ?? null]);
            app(RecordEppOperation::class)->execute($domain, 'register', 'completed', $result, $result['epp_code'] ?? null);

            $updated = $domain->refresh();
            DomainRegistered::dispatch($updated);

            return $updated;
        });
    }
}
