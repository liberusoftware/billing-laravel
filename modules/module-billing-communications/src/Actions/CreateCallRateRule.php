<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Events\CallRateRuleCreated;
use Liberu\Billing\Communications\Models\CallRateRule;

final class CreateCallRateRule
{
    /** @param array<string,mixed> $attributes */
    public function handle(int $teamId, array $attributes): CallRateRule
    {
        $name = trim((string) ($attributes['name'] ?? ''));
        $prefix = trim((string) ($attributes['destination_prefix'] ?? ''));
        $rate = (float) ($attributes['rate_per_minute'] ?? -1);
        $increment = (int) ($attributes['billing_increment_seconds'] ?? 60);
        if ($teamId < 1 || $name === '' || $prefix === '' || $rate < 0 || $increment < 1) {
            throw new InvalidArgumentException('Call rate rule details are invalid.');
        }

        return DB::transaction(function () use ($teamId, $name, $prefix, $rate, $increment, $attributes): CallRateRule {
            $rule = CallRateRule::query()->create([
                'team_id' => $teamId,
                'name' => $name,
                'destination_prefix' => $prefix,
                'connection_fee' => max(0, (float) ($attributes['connection_fee'] ?? 0)),
                'rate_per_minute' => $rate,
                'billing_increment_seconds' => $increment,
                'currency' => strtoupper((string) ($attributes['currency'] ?? 'USD')),
                'is_active' => (bool) ($attributes['is_active'] ?? true),
            ]);
            CallRateRuleCreated::dispatch($rule);

            return $rule;
        });
    }
}
