<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Models\VoipAccount;
use Liberu\Billing\Communications\Services\VoiceProviderRegistry;

final readonly class ProvisionVoipAccount
{
    public function __construct(private VoiceProviderRegistry $providers) {}

    public function handle(VoipAccount $account): VoipAccount
    {
        return DB::transaction(function () use ($account): VoipAccount {
            $locked = VoipAccount::query()->lockForUpdate()->findOrFail($account->getKey());
            if ($locked->status === 'cancelled' || $locked->status === 'terminated') {
                throw new InvalidArgumentException('A terminated VoIP account cannot be provisioned.');
            }

            $result = $this->providers->resolve((string) $locked->platform)->provision($locked->toArray());
            $locked->forceFill(['status' => 'active', 'provisioned_at' => $locked->provisioned_at ?? now(), 'platform_synced_at' => now()])->save();

            return $locked->refresh()->setAttribute('provider_result', $result);
        });
    }
}
