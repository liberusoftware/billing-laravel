<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Events\CallDetailRecordIngested;
use Liberu\Billing\Communications\Models\CallDetailRecord;
use Liberu\Billing\Communications\Models\CallRateRule;
use Liberu\Billing\Communications\Models\VoipAccount;
use Liberu\Billing\Communications\Models\VoipFraudAlert;

final class IngestCallDetailRecord
{
    /** @param array<string,mixed> $attributes */
    public function handle(VoipAccount $account, array $attributes): CallDetailRecord
    {
        $externalId = trim((string) ($attributes['external_id'] ?? ''));
        $destination = trim((string) ($attributes['destination'] ?? ''));
        if ($externalId === '' || $destination === '' || blank($attributes['started_at'] ?? null)) {
            throw new InvalidArgumentException('External ID, destination, and start time are required.');
        }

        return DB::transaction(function () use ($account, $attributes, $externalId, $destination): CallDetailRecord {
            $locked = VoipAccount::query()->lockForUpdate()->findOrFail($account->getKey());
            $existing = CallDetailRecord::query()->where('voip_account_id', $locked->getKey())->where('external_id', $externalId)->first();
            if ($existing !== null) {
                return $existing;
            }

            $duration = max(0, (int) ($attributes['duration_seconds'] ?? 0));
            $rule = CallRateRule::query()->forTeam((int) $locked->team_id)->where('is_active', true)->get()
                ->filter(fn (CallRateRule $candidate): bool => str_starts_with($destination, $candidate->destination_prefix))
                ->sortByDesc(fn (CallRateRule $candidate): int => strlen($candidate->destination_prefix))->first();
            [$billable, $cost, $currency] = $this->rate($rule, $duration);
            $cdr = CallDetailRecord::query()->create([
                'team_id' => $locked->team_id, 'voip_account_id' => $locked->getKey(), 'call_rate_rule_id' => $rule?->getKey(),
                'external_id' => $externalId, 'source' => (string) ($attributes['source'] ?? ''), 'destination' => $destination,
                'direction' => (string) ($attributes['direction'] ?? 'outbound'), 'started_at' => $attributes['started_at'],
                'answered_at' => $attributes['answered_at'] ?? null, 'ended_at' => $attributes['ended_at'] ?? null,
                'duration_seconds' => $duration, 'billable_seconds' => $billable, 'rated_cost' => $cost, 'currency' => $currency,
                'disposition' => (string) ($attributes['disposition'] ?? 'unknown'), 'metadata' => $attributes['metadata'] ?? null,
            ]);
            $locked->increment('current_usage_cost', $cost);
            $this->alerts($locked->refresh(), $cdr);

            $record = $cdr->refresh();
            CallDetailRecordIngested::dispatch($record);

            return $record;
        });
    }

    /** @return array{int,float,string} */
    private function rate(?CallRateRule $rule, int $duration): array
    {
        if ($rule === null || $duration === 0) {
            return [0, 0.0, $rule?->currency ?? 'USD'];
        }
        $increment = max(1, (int) $rule->billing_increment_seconds);
        $billable = (int) (ceil($duration / $increment) * $increment);
        $cost = (float) $rule->connection_fee + ($billable / 60) * (float) $rule->rate_per_minute;

        return [$billable, round($cost, 4), (string) $rule->currency];
    }

    private function alerts(VoipAccount $account, CallDetailRecord $cdr): void
    {
        $alerts = [];
        if ((float) $cdr->rated_cost >= (float) config('voip.fraud.single_call_cost', 50)) {
            $alerts[] = ['high_call_cost', 'high', 'Call cost exceeded the configured fraud threshold.'];
        }
        if ((int) $cdr->duration_seconds >= (int) config('voip.fraud.call_duration_seconds', 14400)) {
            $alerts[] = ['long_duration', 'medium', 'Call duration exceeded the configured fraud threshold.'];
        }
        foreach ((array) config('voip.fraud.high_risk_prefixes', []) as $prefix) {
            if (is_string($prefix) && $prefix !== '' && str_starts_with((string) $cdr->destination, $prefix)) {
                $alerts[] = ['high_risk_destination', 'critical', 'Call was placed to a high-risk destination.'];
                break;
            }
        }
        if ($account->credit_limit !== null && (float) $account->current_usage_cost >= (float) $account->credit_limit) {
            $alerts[] = ['credit_limit', 'critical', 'VoIP account usage reached its credit limit.'];
        }
        foreach ($alerts as [$rule, $severity, $message]) {
            VoipFraudAlert::query()->create(['team_id' => $account->team_id, 'voip_account_id' => $account->getKey(), 'call_detail_record_id' => $cdr->getKey(), 'rule' => $rule, 'severity' => $severity, 'message' => $message, 'context' => ['destination' => $cdr->destination, 'rated_cost' => $cdr->rated_cost]]);
        }
    }
}
