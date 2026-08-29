<?php

declare(strict_types=1);

namespace Liberu\Billing\Pricing\Support;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;

final class CustomerReference
{
    public static function assertBelongsToTeam(DatabaseManager $database, mixed $customerId, mixed $teamId): ?int
    {
        if ($customerId === null || $customerId === '') {
            return null;
        }

        $normalizedCustomerId = (int) $customerId;
        if ($normalizedCustomerId < 1 || ! Schema::hasTable('customers')) {
            throw new \InvalidArgumentException('Pricing customer reference is invalid.');
        }

        $customer = $database->table('customers')->where('id', $normalizedCustomerId)->first(['team_id']);
        if ($customer === null || ($customer->team_id !== null && ($teamId === null || (int) $customer->team_id !== (int) $teamId))) {
            throw new \InvalidArgumentException('Pricing customer reference is invalid.');
        }

        return $normalizedCustomerId;
    }
}
