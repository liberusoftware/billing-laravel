<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Actions;

use Illuminate\Support\Facades\DB;
use Liberu\Billing\Provisioning\Enums\ProvisioningState;
use Liberu\Billing\Provisioning\Models\ProvisionedService;

final class CreateProvisionedService
{
    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): ProvisionedService
    {
        $provider = trim((string) ($attributes['provider'] ?? ''));
        if ($provider === '') {
            throw new \InvalidArgumentException('A provisioning provider is required.');
        }

        return DB::transaction(fn (): ProvisionedService => ProvisionedService::query()->create([
            'team_id' => $attributes['team_id'] ?? null,
            'customer_id' => $attributes['customer_id'] ?? null,
            'subscription_id' => $attributes['subscription_id'] ?? null,
            'provider' => $provider,
            'external_id' => $attributes['external_id'] ?? null,
            'state' => $attributes['state'] ?? ProvisioningState::Pending,
            'last_error' => null,
            'metadata' => $attributes['metadata'] ?? [],
        ]));
    }
}
