<?php

declare(strict_types=1);

namespace Liberu\Billing\Provisioning\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Liberu\Billing\Provisioning\Enums\ProvisioningState;
use Liberu\Billing\Provisioning\Models\ProvisionedService;
use Liberu\Billing\Provisioning\Support\CustomerReference;

final class CreateProvisionedService
{
    /** @param array<string,mixed> $attributes */
    public function execute(array $attributes): ProvisionedService
    {
        $provider = trim((string) ($attributes['provider'] ?? ''));
        if ($provider === '') {
            throw new \InvalidArgumentException('A provisioning provider is required.');
        }

        $teamId = $attributes['team_id'] ?? null;
        $customerId = CustomerReference::assertBelongsToTeam(app('db'), $attributes['customer_id'] ?? null, $teamId);

        $subscriptionId = $attributes['subscription_id'] ?? null;
        if ($subscriptionId !== null && Schema::hasTable('billing_subscriptions')) {
            $subscriptionTeam = DB::table('billing_subscriptions')->where('id', (int) $subscriptionId)->value('team_id');
            if ($subscriptionTeam === null || (int) $subscriptionTeam !== (int) ($attributes['team_id'] ?? 0)) {
                throw new \InvalidArgumentException('Provisioning subscription reference is invalid.');
            }
        } elseif ($subscriptionId !== null) {
            throw new \InvalidArgumentException('Provisioning subscription reference is invalid.');
        }

        return DB::transaction(fn (): ProvisionedService => ProvisionedService::query()->create([
            'team_id' => $teamId,
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId,
            'provider' => $provider,
            'external_id' => $attributes['external_id'] ?? null,
            'state' => $attributes['state'] ?? ProvisioningState::Pending,
            'last_error' => null,
            'metadata' => $attributes['metadata'] ?? [],
        ]));
    }
}
