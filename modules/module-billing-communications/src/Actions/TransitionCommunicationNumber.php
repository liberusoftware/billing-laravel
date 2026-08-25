<?php

declare(strict_types=1);

namespace Liberu\Billing\Communications\Actions;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Billing\Communications\Models\CommunicationNumber;

final class TransitionCommunicationNumber
{
    public function handle(CommunicationNumber $number, string $status): CommunicationNumber
    {
        if (! in_array($status, ['available', 'active', 'suspended', 'released', 'failed'], true)) {
            throw new InvalidArgumentException('Communication number status is invalid.');
        }

        return DB::transaction(function () use ($number, $status): CommunicationNumber {
            $number->update(['status' => $status]);

            return $number->refresh();
        });
    }
}
