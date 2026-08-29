<?php

declare(strict_types=1);

namespace Liberu\Billing\Payments\Support;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Schema;

final class CustomerReference
{
    public static function assertBelongsToTeam(DatabaseManager $database, mixed $customerId, mixed $teamId): ?int
    {
        if ($customerId === null) {
            return null;
        }

        if (! Schema::hasTable('customers')) {
            throw new \InvalidArgumentException('Customer reference is invalid.');
        }

        $hasTeam = Schema::hasColumn('customers', 'team_id');
        $customer = $database->table('customers')->where('id', (int) $customerId)->first($hasTeam ? ['team_id'] : ['id']);
        if ($customer === null || ($hasTeam && $customer->team_id !== null && ($teamId === null || (int) $customer->team_id !== (int) $teamId))) {
            throw new \InvalidArgumentException('Customer reference is invalid.');
        }

        return (int) $customerId;
    }
}
