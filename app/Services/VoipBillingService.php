<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\VoipPlatformClient;
use App\Enums\VoipAccountStatus;
use App\Models\CallDetailRecord;
use App\Models\CallRateRule;
use App\Models\UsageRecord;
use App\Models\VoipAccount;
use App\Models\VoipFraudAlert;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VoipBillingService
{
    public function provision(VoipAccount $account, VoipPlatformClient $client): VoipAccount
    {
        if ($account->status === VoipAccountStatus::Terminated) {
            throw new InvalidArgumentException('A terminated VoIP account cannot be provisioned.');
        }

        $client->provisionAccount($account);
        $account->update([
            'status' => VoipAccountStatus::Active,
            'provisioned_at' => $account->provisioned_at ?? now(),
            'platform_synced_at' => now(),
        ]);

        return $account->refresh();
    }

    /**
     * @param  array{
     *   external_id: string, source: string, destination: string,
     *   started_at: string, answered_at?: string|null, ended_at?: string|null,
     *   duration_seconds?: int, disposition?: string, direction?: string,
     *   metadata?: array<string, mixed>
     * }  $data
     */
    public function ingestCdr(VoipAccount $account, array $data): CallDetailRecord
    {
        return DB::transaction(function () use ($account, $data): CallDetailRecord {
            $existing = CallDetailRecord::query()
                ->where('voip_account_id', $account->id)
                ->where('external_id', $data['external_id'])
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $duration = max(0, (int) ($data['duration_seconds'] ?? 0));
            $rule = $this->resolveRate($account->team_id, $data['destination']);
            [$billableSeconds, $cost, $currency] = $this->rate($rule, $duration);

            $cdr = CallDetailRecord::query()->create([
                ...$data,
                'team_id' => $account->team_id,
                'voip_account_id' => $account->id,
                'call_rate_rule_id' => $rule?->id,
                'duration_seconds' => $duration,
                'billable_seconds' => $billableSeconds,
                'rated_cost' => $cost,
                'currency' => $currency,
            ]);

            $account->increment('current_usage_cost', $cost);

            if ($account->subscription_id !== null && $billableSeconds > 0) {
                UsageRecord::query()->create([
                    'subscription_id' => $account->subscription_id,
                    'metric_name' => 'voip_minutes',
                    'quantity' => round($billableSeconds / 60, 2),
                    'recorded_at' => $cdr->started_at,
                    'processed' => false,
                ]);
            }

            $this->monitorFraud($account->refresh(), $cdr);

            return $cdr->refresh();
        });
    }

    public function resolveRate(int $teamId, string $destination): ?CallRateRule
    {
        return CallRateRule::query()
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (CallRateRule $rule): bool => str_starts_with($destination, $rule->destination_prefix))
            ->sortByDesc(fn (CallRateRule $rule): int => strlen($rule->destination_prefix))
            ->first();
    }

    /**
     * @return array{int, float, string}
     */
    private function rate(?CallRateRule $rule, int $durationSeconds): array
    {
        if ($rule === null) {
            return [0, 0.0, 'USD'];
        }

        if ($durationSeconds === 0) {
            return [0, 0.0, $rule->currency];
        }

        $increment = max(1, $rule->billing_increment_seconds);
        $billableSeconds = (int) (ceil($durationSeconds / $increment) * $increment);
        $cost = (float) $rule->connection_fee
            + ($billableSeconds / 60) * (float) $rule->rate_per_minute;

        return [$billableSeconds, round($cost, 4), $rule->currency];
    }

    private function monitorFraud(VoipAccount $account, CallDetailRecord $cdr): void
    {
        $alerts = [];
        $costThreshold = (float) config('voip.fraud.single_call_cost', 50);
        $durationThreshold = (int) config('voip.fraud.call_duration_seconds', 14400);

        if ((float) $cdr->rated_cost >= $costThreshold) {
            $alerts[] = ['high_call_cost', 'high', 'Call cost exceeded the configured fraud threshold.'];
        }

        if ($cdr->duration_seconds >= $durationThreshold) {
            $alerts[] = ['long_duration', 'medium', 'Call duration exceeded the configured fraud threshold.'];
        }

        foreach ((array) config('voip.fraud.high_risk_prefixes', []) as $prefix) {
            if (is_string($prefix) && $prefix !== '' && str_starts_with($cdr->destination, $prefix)) {
                $alerts[] = ['high_risk_destination', 'critical', 'Call was placed to a high-risk destination.'];
                break;
            }
        }

        if ($account->credit_limit !== null
            && (float) $account->current_usage_cost >= (float) $account->credit_limit) {
            $alerts[] = ['credit_limit', 'critical', 'VoIP account usage reached its credit limit.'];
        }

        foreach ($alerts as [$rule, $severity, $message]) {
            VoipFraudAlert::query()->create([
                'team_id' => $account->team_id,
                'voip_account_id' => $account->id,
                'call_detail_record_id' => $cdr->id,
                'rule' => $rule,
                'severity' => $severity,
                'message' => $message,
                'context' => ['destination' => $cdr->destination, 'rated_cost' => $cdr->rated_cost],
            ]);
        }
    }
}
