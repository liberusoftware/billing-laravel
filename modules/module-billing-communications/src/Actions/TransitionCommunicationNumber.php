<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Events\CommunicationNumberStatusChanged;
use Liberu\Billing\Communications\Models\CommunicationNumber;

final class TransitionCommunicationNumber
{
    public function handle(CommunicationNumber $number, string $status): CommunicationNumber
    {
        if (! in_array($status, ['available', 'active', 'suspended', 'released', 'failed'], true)) {
            throw new InvalidArgumentException('Communication number status is invalid.');
        }

        return DB::transaction(function () use ($number, $status): CommunicationNumber {
            $locked = CommunicationNumber::query()->lockForUpdate()->findOrFail($number->getKey());
            if ($locked->status === 'released' && $status !== 'released') {
                throw new InvalidArgumentException('Released communication numbers cannot be reactivated.');
            }

            $locked->update(['status' => $status]);
            $updated = $locked->refresh();
            CommunicationNumberStatusChanged::dispatch($updated);

            return $updated;
        });
    }
}
