<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Events\VoipAccountCreated;
use Liberu\Billing\Communications\Models\VoipAccount;

final class CreateVoipAccount
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): VoipAccount
    {
        $required = ['customer_id', 'platform', 'sip_username', 'sip_secret'];
        foreach ($required as $field) {
            if (blank($attributes[$field] ?? null)) {
                throw new InvalidArgumentException('Customer, platform, and SIP credentials are required.');
            }
        }

        return DB::transaction(function () use ($teamId, $attributes): VoipAccount {
            $account = VoipAccount::query()->create([
                'team_id' => $teamId,
                'customer_id' => (int) $attributes['customer_id'],
                'subscription_id' => isset($attributes['subscription_id']) ? (int) $attributes['subscription_id'] : null,
                'platform' => trim((string) $attributes['platform']),
                'status' => 'pending',
                'sip_username' => trim((string) $attributes['sip_username']),
                'sip_secret' => (string) $attributes['sip_secret'],
                'caller_id' => $attributes['caller_id'] ?? null,
                'credit_limit' => $attributes['credit_limit'] ?? null,
                'max_concurrent_calls' => max(1, (int) ($attributes['max_concurrent_calls'] ?? 1)),
                'international_enabled' => (bool) ($attributes['international_enabled'] ?? false),
            ]);
            VoipAccountCreated::dispatch($account);

            return $account;
        });
    }
}
