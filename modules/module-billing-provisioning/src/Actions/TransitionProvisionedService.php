<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Actions;

use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;
use Liberu\Billing\Provisioning\Enums\ProvisioningState;
use Liberu\Billing\Provisioning\Models\ProvisionedService;

final readonly class TransitionProvisionedService
{
    public function __construct(private DatabaseManager $database) {}

    public function execute(ProvisionedService $service, ProvisioningState $state, ?string $error = null): ProvisionedService
    {
        if (! $this->allowed($service->state, $state)) {
            throw new InvalidArgumentException("Invalid provisioning transition from [{$service->state->value}] to [{$state->value}].");
        }

        return $this->database->transaction(function () use ($service, $state, $error): ProvisionedService {
            $service->forceFill(['state' => $state, 'last_error' => $error])->save();

            return $service->refresh();
        });
    }

    private function allowed(ProvisioningState $from, ProvisioningState $to): bool
    {
        return match ($from) {
            ProvisioningState::Pending => in_array($to, [ProvisioningState::Provisioning, ProvisioningState::Failed], true),
            ProvisioningState::Provisioning => in_array($to, [ProvisioningState::Active, ProvisioningState::Failed], true),
            ProvisioningState::Active => in_array($to, [ProvisioningState::Suspended, ProvisioningState::Deprovisioning, ProvisioningState::Failed], true),
            ProvisioningState::Suspended => in_array($to, [ProvisioningState::Active, ProvisioningState::Deprovisioning, ProvisioningState::Failed], true),
            ProvisioningState::Deprovisioning => in_array($to, [ProvisioningState::Deprovisioned, ProvisioningState::Failed], true),
            ProvisioningState::Failed => in_array($to, [ProvisioningState::Provisioning, ProvisioningState::Deprovisioning], true),
            ProvisioningState::Deprovisioned => false,
        };
    }
}
