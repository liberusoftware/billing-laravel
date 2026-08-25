<?php

declare(strict_types=1);

namespace Liberu\Billing\Domains\Actions;

use Illuminate\Database\DatabaseManager;
use Liberu\Billing\Domains\Models\Domain;
use Liberu\Billing\Domains\Services\RegistrarManager;

final readonly class TransferDomain
{
    public function __construct(private DatabaseManager $database, private RegistrarManager $registrars) {}

    public function execute(Domain $domain, string $authCode, mixed $customerId, ?string $registrar = null): Domain
    {
        if (trim($authCode) === '') {
            throw new \InvalidArgumentException('An EPP authorization code is required.');
        }
        $targetRegistrar = $registrar ?: (string) $domain->registrar;
        $result = $this->registrars->client($targetRegistrar)->transferDomain($domain->name, $authCode, $customerId);
        if ($result === null) {
            throw new \RuntimeException('The registrar rejected the domain transfer.');
        }

        return $this->database->transaction(function () use ($domain, $result, $targetRegistrar): Domain {
            $domain->update(['registrar' => $targetRegistrar, 'status' => 'transfer_pending', 'transfer_status' => 'pending', 'expires_at' => $result['expiration_date'] ?? $domain->expires_at]);
            app(RecordEppOperation::class)->execute($domain, 'transfer', 'pending', $result, $result['epp_code'] ?? null);

            return $domain->refresh();
        });
    }
}
